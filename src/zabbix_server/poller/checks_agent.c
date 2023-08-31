/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "checks_agent.h"
#include "../sysinfo.h"

#include "zbxsysinfo.h"
#include "zbxjson.h"

#if !(defined(HAVE_GNUTLS) || defined(HAVE_OPENSSL))
extern unsigned char	program_type;
#endif

void	zbx_agent_handle_response(zbx_socket_t *s, ssize_t received_len, int *ret, char *addr, AGENT_RESULT *result)
{
	zabbix_log(LOG_LEVEL_DEBUG, "get value from agent result: '%s'", s->buffer);

	if (0 == strcmp(s->buffer, ZBX_NOTSUPPORTED))
	{
		/* 'ZBX_NOTSUPPORTED\0<error message>' */
		if (sizeof(ZBX_NOTSUPPORTED) < s->read_bytes)
			SET_MSG_RESULT(result, zbx_dsprintf(NULL, "%s", s->buffer + sizeof(ZBX_NOTSUPPORTED)));
		else
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Not supported by Zabbix Agent"));

		*ret = NOTSUPPORTED;
	}
	else if (0 == strcmp(s->buffer, ZBX_ERROR))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Zabbix Agent non-critical error"));
		*ret = AGENT_ERROR;
	}
	else if (0 == received_len)
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Received empty response from Zabbix Agent at [%s]."
				" Assuming that agent dropped connection because of access permissions.",
				addr));
		*ret = NETWORK_ERROR;
	}
	else
		zbx_set_agent_result_type(result, ITEM_VALUE_TYPE_TEXT, s->buffer);
}
/******************************************************************************
 *                                                                            *
 * Purpose: retrieve data from Zabbix agent                                   *
 *                                                                            *
 * Parameters: item             - [IN] item we are interested in              *
 *             timeout          - [IN]                                        *
 *             config_source_ip - [IN]                                        *
 *             result           - [OUT]                                       *
 *                                                                            *
 * Return value: SUCCEED - data successfully retrieved and stored in result   *
 *                         and result_str (as string)                         *
 *               NETWORK_ERROR - network related error occurred               *
 *               NOTSUPPORTED - item not supported by the agent               *
 *               AGENT_ERROR - uncritical error on agent side occurred        *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Comments: error will contain error message                                 *
 *                                                                            *
 ******************************************************************************/
int	get_value_agent(const zbx_dc_item_t *item, const char *config_source_ip, AGENT_RESULT *result)
{
	zbx_socket_t	s;
	const char	*tls_arg1, *tls_arg2;
	int		timeout_sec;
	int		ret = SUCCEED;
	ssize_t		received_len;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() host:'%s' addr:'%s' key:'%s' conn:'%s'", __func__, item->host.host,
			item->interface.addr, item->key, zbx_tcp_connection_type_name(item->host.tls_connect));

	switch (item->host.tls_connect)
	{
		case ZBX_TCP_SEC_UNENCRYPTED:
			tls_arg1 = NULL;
			tls_arg2 = NULL;
			break;
#if defined(HAVE_GNUTLS) || defined(HAVE_OPENSSL)
		case ZBX_TCP_SEC_TLS_CERT:
			tls_arg1 = item->host.tls_issuer;
			tls_arg2 = item->host.tls_subject;
			break;
		case ZBX_TCP_SEC_TLS_PSK:
			tls_arg1 = item->host.tls_psk_identity;
			tls_arg2 = item->host.tls_psk;
			break;
#else
		case ZBX_TCP_SEC_TLS_CERT:
		case ZBX_TCP_SEC_TLS_PSK:
			SET_MSG_RESULT(result, zbx_dsprintf(NULL, "A TLS connection is configured to be used with agent"
					" but support for TLS was not compiled into %s.",
					get_program_type_string(program_type)));
			ret = CONFIG_ERROR;
			goto out;
#endif
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid TLS connection parameters."));
			ret = CONFIG_ERROR;
			goto out;
	}

	if (NULL != item->timeout)
		zbx_is_time_suffix(item->timeout, &timeout_sec, ZBX_LENGTH_UNLIMITED);
	else
		timeout_sec = sysinfo_get_config_timeout();


	if (SUCCEED == zbx_tcp_connect(&s, config_source_ip, item->interface.addr, item->interface.port, timeout_sec + 1,
			item->host.tls_connect, tls_arg1, tls_arg2))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "Sending [%s]", item->key);

		if (SUCCEED != zbx_tcp_send_ext(&s, item->key, strlen(item->key), (zbx_uint32_t)timeout_sec, ZBX_TCP_PROTOCOL, 0))
			ret = NETWORK_ERROR;
		else if (FAIL != (received_len = zbx_tcp_recv_ext(&s, 0, 0)))
			ret = SUCCEED;
		else if (SUCCEED != zbx_socket_check_deadline(&s))
			ret = TIMEOUT_ERROR;
		else
			ret = NETWORK_ERROR;
	}
	else
	{
		ret = NETWORK_ERROR;
		goto out;
	}

	if (SUCCEED == ret)
		zbx_agent_handle_response(&s, received_len, &ret, item->interface.addr, result);
	else
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Get value from agent failed: %s", zbx_socket_strerror()));

	zbx_tcp_close(&s);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

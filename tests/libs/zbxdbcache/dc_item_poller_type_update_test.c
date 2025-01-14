/*
** Zabbix
** Copyright (C) 2001-2024 Zabbix SIA
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

#include "dc_item_poller_type_update_test.h"

void	DCitem_poller_type_update_test(ZBX_DC_ITEM *dc_item, const ZBX_DC_HOST *dc_host, int flags)
{
	DCitem_poller_type_update(dc_item, dc_host, flags);
}

void	init_test_configuration_cache(zbx_get_config_forks_f get_config_forks)
{
	get_config_forks_cb = get_config_forks;
	config = (ZBX_DC_CONFIG *)zbx_malloc(NULL, sizeof(ZBX_DC_CONFIG));
	zbx_hashset_create(&config->snmpitems, 1, ZBX_DEFAULT_UINT64_HASH_FUNC, ZBX_DEFAULT_UINT64_COMPARE_FUNC);
}

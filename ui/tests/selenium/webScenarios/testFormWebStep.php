<?php
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
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

require_once dirname(__FILE__).'/../../include/CLegacyWebTest.php';

use Facebook\WebDriver\WebDriverBy;

/**
 * @backup httptest
 */
class testFormWebStep extends CLegacyWebTest {

	public static function steps() {
		return [
			// Empty step name
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Empty step name',
					'url' => 'http://www.zabbix.com',
					'errors' => [
						'Incorrect value for field "name": cannot be empty.'
					]
				]
			],
			// Step name - max length 64
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Max step length',
					'step_name' => 'In mollis eros vel tempor bibendum. Maecenas nec fringilla felis',
					'url' => 'http://www.zabbix.com'
				]
			],
			// Empty step url
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Empty step url',
					'step_name' => 'Step with empty step url',
					'errors' => [
						'Incorrect value for field "url": cannot be empty.'
					]
				]
			],
			// Query - empty name
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Empty query name',
					'step_name' => 'Step with empty query name',
					'url' => 'http://www.zabbix.com',
					'query' => [
						['value' => 'test']
					],
					'errors' => [
						'Incorrect value for field "query_fields/1/name": cannot be empty.'
					]
				]
			],
			// Parse url
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Parse url',
					'step_name' => 'Step parse url',
					'url' => 'https://intranet.zabbix.com/secure/admin.jspa?login=admin&password=s00p3r%24ecr3%26',
					'parse' => true,
					'parse_query' => [
						['name' => 'login', 'value' => 'admin'],
						['name' => 'password', 'value' => 's00p3r$ecr3&']
					],
					'check_url' => 'https://intranet.zabbix.com/secure/admin.jspa'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Existing query fields merge',
					'step_name' => 'Step existing query fields merge',
					'url' => 'https://intranet.zabbix.com/secure/admin.jspa?password=s00p3r%24ecr3%26',
					'query' => [
						['name' => 'login', 'value' => 'admin']
					],
					'parse' => true,
					'parse_query' => [
						['name' => 'login', 'value' => 'admin'],
						['name' => 'password', 'value' => 's00p3r$ecr3&']
					],
					'check_url' => 'https://intranet.zabbix.com/secure/admin.jspa'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'After parse query fields remain unchanged',
					'step_name' => 'Step after parse query fields remain unchanged',
					'url' => 'https://intranet.zabbix.com/secure/admin.jspa?login=admin&password=s00p3r%24ecr3%26',
					'parse' => true,
					'parse_query' => [
						['name' => 'login', 'value' => 'admin'],
						['name' => 'password', 'value' => 's00p3r$ecr3&']
					],
					'check_url' => 'https://intranet.zabbix.com/secure/admin.jspa'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Query field duplicates not replaced',
					'step_name' => 'Step query field duplicates not replaced',
					'url' => 'https://intranet.zabbix.com/secure/admin.jspa?login=user&password=a123%24bcd4%26',
					'query' => [
						['name' => 'login', 'value' => 'admin'],
						['name' => 'login', 'value' => 'user'],
						['name' => 'password', 'value' => 'password']
					],
					'parse' => true,
					'parse_query' => [
						['name' => 'login', 'value' => 'admin'],
						['name' => 'login', 'value' => 'user'],
						['name' => 'password', 'value' => 'password'],
						['name' => 'login', 'value' => 'user'],
						['name' => 'password', 'value' => 'a123$bcd4&']
					],
					'check_url' => 'https://intranet.zabbix.com/secure/admin.jspa'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'URL fragment part ignored',
					'step_name' => 'Step URL fragment part ignored',
					'url' => 'http://www.zabbix.com/enterprise_ready#test',
					'parse' => true,
					'parse_query' => [
						['name' => '', 'value' => '']
					],
					'check_url' => 'http://www.zabbix.com/enterprise_ready'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Call to Prometheus API',
					'step_name' => 'Step call to Prometheus API',
					'url' => 'http://localhost:9090/api/v1/query?query=irate(node_network_transmit_bytes_total\{device!="lo",instance="192.168.150.101"}[1m])',
					'parse' => true,
					'parsed_query' => [
						['name' => 'query', 'value' => 'irate(node_network_transmit_bytes_total\{device!="lo",instance="192.168.150.101"}[1m])']
					],
					'check_url' => 'http://localhost:9090/api/v1/query'
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'URL parse validation',
					'step_name' => 'Step URL parse validation',
					'url' => 'http://localhost/zabbix/index.php?test=%11',
					'parse' => true,
					'errors' => 'Failed to parse URL. URL is not properly encoded.'
				]
			],
			// Post - empty name
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Empty post name',
					'step_name' => 'Step with empty post name',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['value' => 'test']
					],
					'errors' => [
						'Incorrect value for field "post_fields/1/name": cannot be empty.'
					]
				]
			],
			// Post name - max length 255
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Post name - max length',
					'step_name' => 'Step post name - max length',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'qwertyuiopqwertyuiopqwertyuiopqwertyui'.
							'opqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwe.'.
							'rtyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqw'.
							'ertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwer'.
							'tyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiop12345678901234',
							'value' => 'qwertyuiopqwertyuiopqwertyuiopqwertyui'.
							'opqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwe.'.
							'rtyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqw'.
							'ertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwer'.
							'tyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiop12345678901234']
					]
				]
			],
			// Post - from form data to raw data
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From form data to raw data',
					'step_name' => 'Step from form data to raw data',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'zab bix', 'value' => 'tes&t']
					],
					'raw' => true,
					'check_raw' => 'zab%20bix=tes%26t'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From form data to raw data cyrillic',
					'step_name' => 'Step from form data to raw data, cyrillic',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'тест', 'value' => 'тест']
					],
					'raw' => true,
					'check_raw' => '%D1%82%D0%B5%D1%81%D1%82=%D1%82%D0%B5%D1%81%D1%82'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From form data to raw data symbols',
					'step_name' => 'Step from form data to raw data, symbols',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => '!@#$%^&*()', 'value' => '!@#$%^&*()']
					],
					'raw' => true,
					'check_raw' => '!%40%23%24%25%5E%26*()=!%40%23%24%25%5E%26*()'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From form to raw with two post data',
					'step_name' => 'Step from form to raw with two post data',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'zabbix', 'value' => 'test'],
						['name' => '&Günter']
					],
					'raw' => true,
					'check_raw' => 'zabbix=test&%26G%C3%BCnter'
				]
			],
			// Post - from raw data to form data
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From raw data to form data',
					'step_name' => 'Step from raw data to form data',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'login=Admin&password={{password}.urlencode()}',
					'check_post' => [
						['name' => 'login', 'value' => 'Admin'],
						['name' => 'password', 'value' => '{{password}.urlencode()}']
					],
					'raw' => true,
					'to_form' => true
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From raw data to form data automatic encoding',
					'step_name' => 'Step from raw data to form data automatic encoding',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'login&enter=Sign+in%26',
					'check_post' => [
						['name' => 'login'],
						['name' => 'enter', 'value' => 'Sign in&']
					],
					'raw' => true,
					'to_form' => true
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'From raw data to form data, cyrillic',
					'step_name' => 'Step from raw data to form data, cyrillic',
					'url' => 'http://www.zabbix.com',
					'raw_data' => '%D1%82%D0%B5%D1%81%D1%82=%D1%82%D0%B5%D1%81%D1%82',
					'check_post' => [
						['name' => 'тест', 'value' => 'тест']
					],
					'raw' => true,
					'to_form' => true
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'Post data validation value without name',
					'step_name' => 'Step post data validation value without name',
					'url' => 'http://www.zabbix.com',
					'raw_data' => '=value',
					'raw' => true,
					'to_form' => true,
					'errors' => 'Cannot convert POST data: Values without names are not allowed in form fields.'
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'Post data validation percent encoding pair is malformed',
					'step_name' => 'Step post data validation percent encoding pair is malformed',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'test=%11',
					'raw' => true,
					'to_form' => true,
					'errors' => 'Cannot convert POST data: Data is not properly encoded.'
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'Post data validation non-printable characters',
					'step_name' => 'Step post data validation non-printable characters',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'value=%00',
					'raw' => true,
					'to_form' => true,
					'errors' => 'Cannot convert POST data: Data is not properly encoded.'
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'Post data validation pair value contains unencoded “=” character',
					'step_name' => 'Step post data validation pair value contains unencoded “=” char',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'name=val=ue',
					'raw' => true,
					'to_form' => true,
					'errors' => 'Cannot convert POST data: Data is not properly encoded.'
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'Post data validation non-unicode encodings',
					'step_name' => 'Step post data validation non-unicode encodings',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'value=%EA%EE%EB%E1%E0%F1%EA%E8',
					'raw' => true,
					'to_form' => true,
					'errors' => 'Cannot convert POST data: URIError: URI malformed'
				]
			],
			[
				[
					'expected' => TEST_ERROR,
					'name' => 'Post data validation field name exceeds 255 characters',
					'step_name' => 'Step post data validation field name exceeds 255 characters',
					'url' => 'http://www.zabbix.com',
					'raw_data' => 'qwertyuiopqwertyuiopqwertyuiopqwertyui'.
							'opqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwe.'.
							'rtyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqw'.
							'ertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwer'.
							'tyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiop123456789012345',
					'raw' => true,
					'to_form' => true,
					'errors' => 'Cannot convert POST data: Name of the form field should not exceed 255 characters.'
				]
			],
			// Variables - just numbers
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Variables - just numbers',
					'step_name' => 'Step variables -just numbers',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => '{1234567890}', 'value' => '123456']
					]
				]
			],
			// Variables - symbols
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Variables - just symbols',
					'step_name' => 'Step variables -just symbols',
					'url' => 'http://www.zabbix.com',
					'name' => 'Variables -symbols',
					'variables' => [
						['name' => '{!@#$%^&*()_+:"|<>?,./}']
					]
				]
			],
			// Variables - 255 max allowed
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Variables - 255 length',
					'step_name' => 'Step variables -255 length',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => '{qwertyuiopqwertyuiopqwertyuiopqwertyui'.
							'opqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwe.'.
							'rtyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqw'.
							'ertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwer'.
							'tyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiop123456789012}']
					]
				]
			],
			// Variables - without {}
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Variables - without {}',
					'step_name' => 'Step variables -without {}',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => 'test']
					],
					'errors' => [
						'Incorrect value for field "variables/1/name": is not enclosed in {} or is malformed.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Variables - without {}',
					'step_name' => 'Step variables -without {}',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => '{test']
					],
					'errors' => [
						'Incorrect value for field "variables/1/name": is not enclosed in {} or is malformed.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Variables - without {}',
					'step_name' => 'Step variables -without {}',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => 'test}']
					],
					'errors' => [
						'Incorrect value for field "variables/1/name": is not enclosed in {} or is malformed.'
					]
				]
			],
			// Variables - with the same names
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Variables - with the same names',
					'step_name' => 'Step variables -with the same names',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => '{test}'],
						['name' => '{test}']
					],
					'errors' => [
						'Incorrect value for field "variables/2": value (name)=({test}) already exists.'
					]
				]
			],
			// Variables - two different
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Variables - two different',
					'step_name' => 'Step variables -two different',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['name' => '{test1}', 'value' => 'test1'],
						['name' => '{test2}', 'value' => 'test1']
					]
				]
			],
			// Variables - empty name
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Variables - empty name',
					'step_name' => 'Step variables -empty name',
					'url' => 'http://www.zabbix.com',
					'variables' => [
						['value' => 'test']
					],
					'errors' => [
						'Incorrect value for field "variables/1/name": cannot be empty.'
					]
				]
			],
			// Headers - just numbers
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Headers - just numbers',
					'step_name' => 'Step headers -just numbers',
					'url' => 'http://www.zabbix.com',
					'name' => 'Headers -just numbers',
					'headers' => [
						['name' => '1234567890', 'value' => '123456']
					]
				]
			],
			// Headers - just symbols
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Headers - just symbols',
					'step_name' => 'Step headers -just symbols',
					'url' => 'http://www.zabbix.com',
					'headers' => [
						['name' => '!@#$%^&*()_+:"{}|<>?,./', 'value' => '!@#$%^&*()_+:"{}|<>?,./']
					]
				]
			],
			// Headers - 255 length
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Headers - 255 length',
					'step_name' => 'Step headers -255 length',
					'url' => 'http://www.zabbix.com',
					'headers' => [
						['name' => 'qwertyuiopqwertyuiopqwertyuiopqwertyui'.
							'opqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwe.'.
							'rtyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqw'.
							'ertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwer'.
							'tyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiop12345678901234',
							'value' => 'qwertyuiopqwertyuiopqwertyuiopqwertyui'.
							'opqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwe.'.
							'rtyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqw'.
							'ertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwer'.
							'tyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiopqwertyuiop12345678901234']
					]
				]
			],
			// Headers - the same names and values
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Headers - the same names and values',
					'step_name' => 'Step headers -the same names and values',
					'url' => 'http://www.zabbix.com',
					'headers' => [
						['name' => 'test', 'value' => 'test_value'],
						['name' => 'test', 'value' => 'test_value']
					]
				]
			],
			// Headers - empty value
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Headers - empty value',
					'step_name' => 'Step headers -empty value',
					'url' => 'http://www.zabbix.com',
					'headers' => [
						['name' => 'test']
					]
				]
			],
			// Headers - empty name
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Headers - empty name',
					'step_name' => 'Step headers -empty name',
					'url' => 'http://www.zabbix.com',
					'headers' => [
						['value' => 'test']
					],
					'errors' => [
						'Incorrect value for field "headers/1/name": cannot be empty.'
					]
				]
			],
			// Retrieve mode
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Retrieve mode',
					'step_name' => 'Step retrieve mode headers',
					'url' => 'http://www.zabbix.com',
					'retrieve' => 'Headers'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Retrieve mode with required string',
					'step_name' => 'Step retrieve mode headers and required string',
					'url' => 'http://www.zabbix.com',
					'retrieve' => 'Headers',
					'string' => 'Zabbix'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Retrieve mode as headers and with post value',
					'step_name' => 'Step retrieve only headers with post value',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['value' => 'test']
					],
					'retrieve' => 'Headers'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Retrieve mode as headers and with post name',
					'step_name' => 'Step retrieve mode headers with post name',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'test']
					],
					'retrieve' => 'Headers'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Retrieve mode as headers and with post value and name',
					'step_name' => 'Step retrieve mode headers with post value and name',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'xxx' , 'value' => 'yyy']
					],
					'retrieve' => 'Headers'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Retrieve mode as Body and headers',
					'step_name' => 'Step retrieve mode body and headers',
					'url' => 'http://www.zabbix.com',
					'retrieve' => 'Body and headers'
				]
			],
			// Timeout
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Timeout -1',
					'step_name' => 'Step timeout -1',
					'url' => 'http://www.zabbix.com',
					'timeout' => '-1',
					'errors' => [
						'Incorrect value for field "timeout": a time unit is expected.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Timeout 3601',
					'step_name' => 'Step timeout 3601',
					'url' => 'http://www.zabbix.com',
					'timeout' => 3601,
					'errors' => [
						'Incorrect value for field "timeout": value must be one of 1-3600.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Timeout 3601',
					'step_name' => 'Step timeout 3601',
					'url' => 'http://www.zabbix.com',
					'timeout' => 0,
					'errors' => [
						'Incorrect value for field "timeout": value must be one of 1-3600.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Timeout string',
					'step_name' => 'Step timeout string',
					'url' => 'http://www.zabbix.com',
					'timeout' => 'abc',
					'errors' => [
						'Incorrect value for field "timeout": a time unit is expected.'
					]
				]
			],
			// Required status codes
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Required status codes - symbols',
					'step_name' => 'Step required status codes - symbols',
					'url' => 'http://www.zabbix.com',
					'code' => 'abcd',
					'errors' => [
						'Invalid response code "abcd".'
					]
				]
			],
			// Fill all form
			[
					[
					'expected' => TEST_GOOD,
					'name' => 'Fill all step form',
					'step_name' => 'Fill all step form',
					'url' => 'http://www.zabbix.com',
					'parse' => true,
					'post' => [
						['name' => 'post', 'value' => 'test_post']
					],
					'query' => [
						['name' => 'query', 'value' => 'test_query']
					],
					'variables' => [
						['name' => '{variable}', 'value' => 'test_variable']
					],
					'headers' => [
						['name' => 'header', 'value' => 'test_header']
					],
					'timeout' => 3600,
					'string' => 'Zabbix',
					'code' => 200,
					'dbCheck' => true
				]
			],
			[
					[
					'expected' => TEST_GOOD,
					'name' => 'Fill all step form and raw data',
					'step_name' => 'Fill all step form and raw data',
					'url' => 'http://www.zabbix.com',
					'parse' => true,
					'post' => [
						['name' => 'zab bix', 'value' => 'tes&t']
					],
					'raw' => true,
					'check_raw' => 'zab%20bix=tes%26t',
					'query' => [
						['name' => 'query', 'value' => 'test_query']
					],
					'variables' => [
						['name' => '{variable}', 'value' => 'test_variable']
					],
					'headers' => [
						['name' => 'header', 'value' => 'test_header']
					],
					'retrieve' => 'Body and headers',
					'timeout' => 1,
					'string' => 'Zabbix',
					'code' => 404,
					'dbCheck' => true
				]
			],
			// Take a screenshots
			[
				[
					'expected' => TEST_GOOD,
					'name' => 'Take a screenshots',
					'step_name' => 'Fill all step form',
					'url' => 'http://www.zabbix.com',
					'post' => [
						['name' => 'post', 'value' => 'test_post'],
						['name' => 'post2', 'value' => 'test_post2'],
						['name' => 'post3', 'value' => 'test_post3']
					],
					'query' => [
						['name' => 'query', 'value' => 'test_query'],
						['name' => 'query2', 'value' => 'test_query2'],
						['name' => 'query3', 'value' => 'test_query3']
					],
					'headers' => [
						['name' => 'header', 'value' => 'test_header'],
						['name' => 'header2', 'value' => 'test_header2'],
						['name' => 'header3', 'value' => 'test_header3']
					],
					'timeout' => 3600,
					'screenshot' => true
				]
			]
		];
	}

	/**
	 * Add name-value pairs to specific context.
	 *
	 * @param string $context	xpath containing context of name-pairs.
	 * @param array  $items		name-value pairs to be added.
	 */
	protected function addPairs($context, $items) {
		$parent = $this->query('xpath', $context)->one();
		$element = $parent->query('xpath:.//tr[contains(@class, "form_row")]')->all()->last();

		foreach($items as $item) {
			foreach ($item as $field => $value) {
				$this->query('xpath', $context.'//textarea[@placeholder="'.$field.'"]')->one()->waitUntilPresent();
				$input = $element->query('xpath:.//textarea[@placeholder="'.$field.'"]')->one();
				$input->sendKeys($value);

				// Fire onchange event.
				$this->webDriver->executeScript('var event = document.createEvent("HTMLEvents");'.
						'event.initEvent("change", false, true);'.
						'arguments[0].dispatchEvent(event);',
						[$element]
				);
			}

			$parent->query('xpath:.//button[text()="Add"]')->one()->click();
			$element = $parent->query('xpath:.//tr[contains(@class, "form_row")]')->all()->last();
		}
	}

	/**
	 * Get name-value pairs from specific context.
	 *
	 * @param string $context	xpath containing context of name-pairs.
	 */
	protected function getPairs($context) {
		$pairs = [];
		$parent = $this->query('xpath', $context)->one();
		$rows = $parent->query('xpath:.//tr[contains(@class, "form_row")]')->all();

		foreach ($rows as $row) {
			$pair = [];
			$inputs = $row->query('xpath:.//textarea')->all();
			foreach ($inputs as $input) {
				$pair[$input->getAttribute('placeholder')] = $input->getValue();
			}

			$pairs[] = $pair;
		}

		return $pairs;
	}

	/**
	 * Serialize pairs as string.
	 *
	 * @param array $pairs	pair array to be serialized.
	 * @return string
	 */
	protected function serializePairs($pairs) {
		$serialized = [];

		foreach ($pairs as $pair) {
			$row = [];
			foreach (['name', 'value'] as $key) {
				$row[] = ((array_key_exists($key, $pair)) ? $pair[$key] : '');
			}

			$serialized[] = implode(':', $row);
		}

		return implode("\n", $serialized);
	}

	/**
	 * @dataProvider steps
	 */
	public function testFormWebStep_CreateSteps($data) {
		$this->zbxTestLogin('httpconf.php?groupid=0&context=host&hostid=40001&form=Create+web+scenario');
		$this->zbxTestCheckTitle('Configuration of web monitoring');
		$this->zbxTestCheckHeader('Web monitoring');

		$this->zbxTestInputTypeWait('name', $data['name']);
		$this->zbxTestTabSwitchById('tab_steps-tab' ,'Steps');
		$this->zbxTestClickXpathWait('//td[@colspan="8"]/button[@class="btn-link js-add-step"]');
		$this->zbxTestLaunchOverlayDialog('New step of web scenario');

		if (array_key_exists('step_name', $data)) {
			$this->zbxTestInputTypeByXpath('//div[@class="overlay-dialogue-body"]//input[@id="name"]', $data['step_name']);
		}

		if (array_key_exists('url', $data)) {
			$this->zbxTestInputTypeByXpath('//div[@class="overlay-dialogue-body"]//input[@id="url"]', $data['url']);
		}

		$fields = [
			'query'		=> 'step-query-fields',
			'post'		=> 'step-post-fields',
			'variables'	=> 'step-variables',
			'headers'	=> 'step-headers'
		];
		foreach ($fields as $field => $data_type) {
			if (array_key_exists($field, $data)) {
				if ($data[$field] === 'post') {
					// Synthetic wait
					sleep(2);
				}
				$this->addPairs('//div[@class="overlay-dialogue-body"]//table[@id="'.$data_type.'"]', $data[$field]);
			}
		}

		if (array_key_exists('parse', $data)) {
			$this->query('button:Parse')->one()->click();
		}

		if (array_key_exists('raw', $data)) {
			$this->zbxTestClickXpath('//label[@for="post_type_1"]');
		}

		if (array_key_exists('to_form', $data)) {
			$this->zbxTestInputType('posts', $data['raw_data']);
			$this->zbxTestClickXpath('//label[@for="post_type_0"]');
		}

		if (array_key_exists('retrieve', $data)) {
			$this->zbxTestClickXpathWait('//div[@class="overlay-dialogue-body"]//ul[@id="retrieve_mode"]'
					. '//label[text()="'.$data['retrieve'].'"]');

			// Check disabled fields for Headers mode.
			$paths = [
				'input[@id="post_type_0"',
				'input[@id="post_type_1"',
				'table[@id="step-post-fields"]//textarea[@placeholder="name"',
				'table[@id="step-post-fields"]//textarea[@placeholder="value"'
			];
			if ($data['retrieve'] === 'Headers') {
				foreach ($paths as $path) {
					$this->zbxTestAssertElementPresentXpath('//div[@class="overlay-dialogue-body"]//'.$path.'][@disabled]');
				}
				$this->zbxTestAssertElementNotPresentXpath("//div[@class='overlay-dialogue-body']//input[@id='required'][@disabled]");
			}
			else {
				foreach ($paths as $path) {
					$this->zbxTestAssertElementNotPresentXpath('//div[@class="overlay-dialogue-body"]//'.$path.'][@disabled]');
				}
			}
		}

		if (array_key_exists('timeout', $data)) {
			$this->zbxTestInputTypeOverwrite('timeout',$data['timeout']);
		}

		if (array_key_exists('string', $data)) {
			$this->zbxTestInputType('required',$data['string']);
		}

		if (array_key_exists('code', $data)) {
			$this->zbxTestInputType('status_codes',$data['code']);
		}

		// Take a screenshot to test draggable object position for query, post and headers fields.
		if (array_key_exists('screenshot', $data)) {
			$this->page->removeFocus();

			foreach (['Post fields', 'Headers', 'Query fields'] as $field) {
				$form = $this->query('id:webscenario-step-form')->asForm()->one();

				if ($field === 'Query fields') {
					COverlayDialogElement::find()->one()->scrollToTop();
				}

				$this->assertScreenshot($form->getField($field), $field);
			}
		}

		if ($data['expected'] != TEST_ERROR) {
			$this->zbxTestClickXpath('//div[@class="overlay-dialogue-footer"]//button[text()="Add"]');
		}

		if (array_key_exists('check_raw', $data)) {
			$this->zbxTestClickLinkTextWait($data['step_name']);
			$this->zbxTestLaunchOverlayDialog('Step of web scenario');
			$raw = $this->zbxTestGetText("//div[@class='overlay-dialogue-body']//textarea[@id='posts']");
			$this->assertEquals($data['check_raw'], $raw);
			$this->zbxTestClickXpath('//div[@class="overlay-dialogue-footer"]//button[text()="Cancel"]');
		}

		foreach (['parse_query' => 'step-query-fields', 'check_post' => 'step-post-fields'] as $key => $data_type) {
			if (!array_key_exists($key, $data)) {
				continue;
			}

			$this->zbxTestClickLinkTextWait($data['step_name']);
			$this->zbxTestLaunchOverlayDialog('Step of web scenario');

			$pairs = $this->getPairs('//div[@class="overlay-dialogue-body"]//table[@id="'.$data_type.'"]');
			$this->assertEquals($this->serializePairs($data[$key]), $this->serializePairs($pairs));

			if (array_key_exists('check_url', $data)) {
				$url = $this->zbxTestGetValue('//div[@class="overlay-dialogue-body"]//input[@id="url"]');
				$this->assertEquals($data['check_url'], $url);
			}
			$this->zbxTestClickXpath('//div[@class="overlay-dialogue-footer"]//button[text()="Cancel"]');
		}

		switch ($data['expected']) {
			case TEST_GOOD:
				$this->zbxTestWaitForPageToLoad();
				COverlayDialogElement::ensureNotPresent();
				$this->zbxTestClickWait('add');
				$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Web scenario added');
				break;
			case TEST_BAD:
				if (array_key_exists('error_msg', $data)) {
					$this->zbxTestWaitUntilMessageTextPresent('msg-bad', $data['error_msg']);
				}
				else {
					$this->zbxTestWaitUntilElementVisible(WebDriverBy::xpath("//div[@class='overlay-dialogue-body']//div[@class='msg-details']"));
				}
				foreach ($data['errors'] as $msg) {
					$this->zbxTestTextPresent($msg);
				}
				break;
			case TEST_ERROR:
				$get_text = $this->zbxTestGetText("//div[@class='overlay-dialogue-body']/span");
				$result = trim(preg_replace('/\s\s+/', ' ', $get_text));
				$this->assertEquals($result, $data['errors']);
				break;
		}

		if (array_key_exists('dbCheck', $data)) {
			$result = DBselect("SELECT * FROM httpstep step LEFT JOIN httpstep_field test ON ".
				"step.httpstepid = test.httpstepid WHERE step.name = '".$data['step_name']."'");
			while ($row = DBfetch($result)) {
				$this->assertEquals($row['url'], $data['url']);
				$this->assertEquals($row['timeout'], $data['timeout']);
				$this->assertEquals($row['required'], $data['string']);
				$this->assertEquals($row['status_codes'], $data['code']);

				if (array_key_exists('check_raw', $data)) {
					$this->assertEquals($row['posts'], $data['check_raw']);
				}

				switch ($row['type']) {
					case 0:
						$this->assertEquals($row['name'], $data['headers'][0]['name']);
						$this->assertEquals($row['value'], $data['headers'][0]['value']);
						break;
					case 1:
						$this->assertEquals($row['name'], $data['variables'][0]['name']);
						$this->assertEquals($row['value'], $data['variables'][0]['value']);
						break;
					case 3:
						$this->assertEquals($row['name'], $data['query'][0]['name']);
						$this->assertEquals($row['value'], $data['query'][0]['value']);
						break;
					case 2:
						$this->assertEquals($row['name'], $data['post'][0]['name']);
						$this->assertEquals($row['value'], $data['post'][0]['value']);
						break;
				}
			}
		}
	}
}
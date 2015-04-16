<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
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


class CWidget {

	private $css_class;
	private $title = null;
	private $controls = null;
	private $headers;

	/**
	 * The contents of the body of the widget.
	 *
	 * @var array
	 */
	protected $body = array();

	/**
	 * The class of the root div element.
	 *
	 * @var string
	 */
	protected $rootClass;

	public function __construct($rootClass = null) {
		$this->css_class = 'header_wide';
		$this->setRootClass($rootClass);
	}

	public function setClass($class = null) {
		if (is_string($class)) {
			$this->css_class = $class;
		}
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function setControls($controls) {
		zbx_value2array($controls);

		$this->controls = $controls;
	}

	public function addHeader($left = SPACE, $right = SPACE) {
		zbx_value2array($right);

		$this->headers[] = array('left' => $left, 'right' => $right);
	}

	public function addItem($items = null) {
		if (!is_null($items)) {
			$this->body[] = $items;
		}
	}

	public function get() {
		$widget = array();
		if ($this->title !== null || $this->controls !== null) {
			$widget[] = $this->createTopHeader();
		}
		if (!empty($this->headers)) {
			$widget[] = $this->createHeader();
		}

		return array($widget, $this->body);
	}

	public function show() {
		echo $this->toString();
	}

	public function toString() {
		$tab = $this->get();

		return unpack_object($tab);
	}

	private function createTopHeader() {
		$body = array(new CTag('h1', 'yes', $this->title), $this->controls);

		return new CDiv($body, 'header-title');
	}

	private function createHeader() {
		$header = reset($this->headers);

		$columnRights = array();

		if (!is_null($header['right'])) {
			foreach ($header['right'] as $right) {
				$columnRights[] = new CDiv($right, 'floatright');
			}
		}

		if ($columnRights) {
			$columnRights = array_reverse($columnRights);
		}

		// header table
		$table = new CTable(null, $this->css_class.' maxwidth');
		$table->setCellSpacing(0);
		$table->setCellPadding(1);
		$table->addRow($this->createHeaderRow($header['left'], $columnRights), 'first');

		if ($this->css_class != 'header_wide') {
			$table->addClass('ui-widget-header ui-corner-all');
		}

		foreach ($this->headers as $num => $header) {
			if ($num > 0) {
				$table->addRow($this->createHeaderRow($header['left'], $header['right']), 'next');
			}
		}

		return new CDiv($table);
	}

	private function createHeaderRow($col1, $col2 = SPACE) {
		$td_r = new CCol($col2, 'header_r right');
		$row = array(new CCol($col1, 'header_l left'), $td_r);
		return $row;
	}

	public function setRootClass($rootClass) {
		$this->rootClass = $rootClass;
	}

	public function getRootClass() {
		return $this->rootClass;
	}
}

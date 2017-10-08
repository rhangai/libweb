<?php
namespace LibWeb\validator\impl;

class Decimal extends \RtLopez\DecimalBCMath {
	
	public function __toString() {
		return $this->format();
	}
	
	public function serializeAPI() {
		return $this->format();
	}
	
};

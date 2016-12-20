<?php

/*

This file is part of Primitive Point of Sale.

    Primitive Point of Sale is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Primitive Point of Sale is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Primitive Point of Sale.  If not, see <http://www.gnu.org/licenses/>.

*/

Class customerAcct {


	public $id;
	public $debts;
	public $credits;
	public $name;
	public $balance;
	public $print_statement;
	
	public function __construct($customer_id)
	{
		$this->id = $customer_id;
		$this->debts = 0.0;
		$this->credits = 0.0;
		$this->balance = 0.0;
	
	}

	public function get_balance()
	{
		$tmp = $this->debts - $this->credits;

		return $tmp;
		//return number_format($tmp, 2);
	}


}

?>

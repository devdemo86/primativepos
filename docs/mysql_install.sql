CREATE database pos;
USE pos;

CREATE TABLE `catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barcode` int(11) DEFAULT NULL,
  `name` char(30) DEFAULT NULL,
  `dept_id` tinyint(4) DEFAULT NULL,
  `vendor_name` char(20) DEFAULT NULL,
  `product_id` char(24) DEFAULT NULL,
  `manufacturer_id` char(24) DEFAULT NULL,
  `vendor_id` char(4) DEFAULT NULL,
  `price` float(7,2) DEFAULT NULL,
  `qty` int(11) DEFAULT '0',
  `override_price` binary(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `product_id` (`product_id`)
);


CREATE TABLE `customer_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `name` char(64) DEFAULT NULL,
  `active` binary(1) DEFAULT '0',
  PRIMARY KEY (`id`)
);


CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `drawer_balance` float(8,2) DEFAULT NULL,
  `action` char(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
);


CREATE TABLE `ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `display_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `subtotal` float(8,2) DEFAULT NULL,
  `tax` float(6,2) DEFAULT NULL,
  `total` float(8,2) DEFAULT NULL,
  `discount` float(8,2) DEFAULT NULL,
  `labor` float(8,2) DEFAULT NULL,
  `freight` float(8,2) DEFAULT NULL,
  `payment_type` char(15) DEFAULT NULL,
  `refund` binary(1) DEFAULT '0',
  `check_no` int(11) DEFAULT NULL,
  `cc_trans_no` int(11) DEFAULT NULL,
  `resale` binary(1) DEFAULT '0',
  `recv_by` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `display_id` (`display_id`),
  KEY `customer_id` (`customer_id`)
);


CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `price` float(8,2) DEFAULT NULL,
  `amount` float(8,2) DEFAULT NULL,
  `name` char(30) DEFAULT NULL,
  `product_id` char(24) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`)
);


CREATE TABLE `users` (
  `name` char(32) DEFAULT NULL,
  `password` char(64) DEFAULT NULL
);

CREATE TABLE `customers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `company` char(64) DEFAULT NULL,
 `last_name` char(50) DEFAULT NULL,
 `first_name` char(50) DEFAULT NULL,
 `mi` char(3) DEFAULT '',
 `address` char(100) DEFAULT NULL,
 `address2` char(100) DEFAULT NULL,
 `city` char(50) DEFAULT NULL,
 `state` char(30) DEFAULT NULL,
 `zip` char(10) DEFAULT NULL,
 `phone` varchar(42) DEFAULT NULL,
 `phone_ext` char(4) DEFAULT NULL,
 `credit` binary(1) DEFAULT '0',
 `tax_exempt` binary(1) DEFAULT '0',
 `active` binary(1) DEFAULT '1',
 `use_company` binary(1) DEFAULT '0',
 `email` char(128) DEFAULT '',
 `print_statement` binary DEFAULT 1,
 PRIMARY KEY (`id`),
 KEY `last_name` (`last_name`,`first_name`)
);

CREATE TABLE `estimate_ticket` (
  `display_id` int(11) NOT NULL DEFAULT '0',
  `customer_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `subtotal` float(8,2) DEFAULT NULL,
  `tax` float(6,2) DEFAULT NULL,
  `total` float(8,2) DEFAULT NULL,
  `discount` float(8,2) DEFAULT NULL,
  `freight` float(8,2) DEFAULT NULL,
  `labor` float(8,2) DEFAULT NULL,
  `payment_type` char(15) DEFAULT NULL,
  `refund` binary(1) DEFAULT '0',
  `check_no` int(11) DEFAULT NULL,
  `cc_trans_no` int(11) DEFAULT NULL,
  `resale` binary(1) DEFAULT '0',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `display_id` (`display_id`),
  KEY `customer_id` (`customer_id`)
);

CREATE TABLE `estimate_transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `price` float(8,2) DEFAULT NULL,
  `amount` float(8,2) DEFAULT NULL,
  `name` char(30) DEFAULT NULL,
  `product_id` char(24) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`)
);


/* CREATE USERS */
 
INSERT INTO customers(company,use_company) VALUES('Customer',1);

INSERT INTO users VALUES('admin', 'admin314');

GRANT ALL PRIVILEGES ON pos.* TO 'pos'@'localhost' IDENTIFIED BY 'pos';
GRANT LOCK TABLES ON pos.* TO 'backupuser'@'localhost' IDENTIFIED BY 'backup7';
FLUSH PRIVILEGES;

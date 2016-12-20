CREATE TABLE estimate_ticket LIKE ticket;
CREATE TABLE estimate_transaction_items LIKE transaction_items;
alter table customers add column email char(128) DEFAULT '';
alter table customers add column print_statement binary default 0;
alter table ticket add column recv_by varchar(24);
create table item_descriptions(id int primary key auto_increment, ticket_id int, item_id int, barcode int, description char(128));

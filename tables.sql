-- Product
create table lamoda_test.product
(
	id int auto_increment
		primary key,
	title varchar(255) null
)
collate=utf8mb4_unicode_ci
;


-- Container
create table lamoda_test.container
(
	id int auto_increment
		primary key,
	title varchar(255) null
)
collate=utf8mb4_unicode_ci
;

-- Container_Product
create table lamoda_test.container_product
(
	container_id int not null,
	product_id int not null,
	primary key (container_id, product_id),
	constraint FK_4D3280E04584665A
		foreign key (product_id) references lamoda_test.product (id)
			on delete cascade,
	constraint FK_4D3280E0BC21F742
		foreign key (container_id) references lamoda_test.container (id)
			on delete cascade
)
collate=utf8mb4_unicode_ci
;

create index IDX_4D3280E04584665A
	on lamoda_test.container_product (product_id)
;

create index IDX_4D3280E0BC21F742
	on lamoda_test.container_product (container_id)
;




CREATE TABLE article_tags(
	id_article_tags BIGSERIAL NOT NULL CONSTRAINT article_tags_pkey PRIMARY KEY,
	id_article BIGINT NOT NULL CONSTRAINT article_tags_fkey REFERENCES article(id_article) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT,
	tag CHARACTER VARYING(30) NOT NULL
) with(
oids=false
);

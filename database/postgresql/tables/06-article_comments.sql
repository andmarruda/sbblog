CREATE TABLE article_comments(
	id_article_comments BIGSERIAL NOT NULL CONSTRAINT article_comments_pkey PRIMARY KEY,
	id_article BIGINT NOT NULL CONSTRAINT article_comments_fkey REFERENCES article(id_article) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT,
	datetime TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
	user_ip CIDR NOT NULL,
	comment_name CHARACTER VARYING(50) NOT NULL,
	comment_text CHARACTER VARYING(350) NOT NULL,
	active BOOLEAN NOT NULL DEFAULT false
) with(
oids=false
);

CREATE TABLE article_visits(
	id_article_visits BIGSERIAL NOT NULL CONSTRAINT article_visits_pkey PRIMARY KEY,
	ip_address CIDR NOT NULL,
	datetime TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
	user_agent TEXT NOT NULL,
	user_details JSONB NOT NULL,
	id_article BIGINT NOT NULL CONSTRAINT article_visits_fkey REFERENCES article(id_article) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT,
	unload_datetime TIMESTAMP WITH TIME ZONE
) with(
oids=false
);

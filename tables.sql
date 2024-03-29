-- Table: public.roles

-- DROP TABLE IF EXISTS public.roles;

CREATE TABLE IF NOT EXISTS public.roles
(
    id integer NOT NULL,
    name character varying(50) COLLATE pg_catalog."default" NOT NULL
    )

    TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.roles
    OWNER to nette_test_task_db_user;




INSERT INTO public.roles (id, name) VALUES (1, 'admin'), (2, 'member');




-- Table: public.users

-- DROP TABLE IF EXISTS public.users;

CREATE TABLE IF NOT EXISTS public.users
(
    id bigint NOT NULL DEFAULT nextval('user_id_seq'::regclass),
    username character varying(255) COLLATE pg_catalog."default" NOT NULL,
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    password character varying(255) COLLATE pg_catalog."default",
    role_id character varying(50) COLLATE pg_catalog."default" NOT NULL DEFAULT 2,
    email_verification_code integer,
    email_verification_status boolean DEFAULT false,
    email_verification_expire timestamp without time zone,
    CONSTRAINT user_pkey PRIMARY KEY (id)
    )

    TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.users
    OWNER to nette_test_task_db_user;




INSERT INTO public.users (username, email, password, role_id, email_verification_status)
VALUES ('admin', 'admin@test.com', '$2y$10$4veMXnXg2LSn5Ks3Czml1e4UgOzr3GYjn7jt2EeXsc89Ktnsy9i62', 1, true);


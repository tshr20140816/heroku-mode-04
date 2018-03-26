CREATE TABLE m_application (
    api_key character varying(36) PRIMARY KEY,
    fqdn character varying(255) NOT NULL,
    user_account character varying(255) NOT NULL,
    dyno_quota bigint DEFAULT -1 NOT NULL,
    dyno_used bigint DEFAULT -1 NOT NULL,
    dyno_used_previous bigint DEFAULT -1 NOT NULL,
    update_flag integer DEFAULT 0 NOT NULL,
    change_time timestamp DEFAULT localtimestamp NOT NULL,
    update_time timestamp DEFAULT localtimestamp NOT NULL,
    select_type integer NOT NULL
);

CREATE FUNCTION set_update_time() RETURNS trigger
    LANGUAGE plpgsql
    AS $$begin
  NEW.update_time := localtimestamp;

  return NEW;
end;
$$;

CREATE TRIGGER update_trigger BEFORE UPDATE ON m_application FOR EACH ROW EXECUTE PROCEDURE set_update_time();

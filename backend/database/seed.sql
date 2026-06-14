--
-- PostgreSQL database dump
--

\restrict JeTmCV3EmBrcQ2aPaR6hBUBWUsYuS3OBzsIDABqyuyzmw6LLhYsCjeV7Yd5ZSCq

-- Dumped from database version 14.23 (Ubuntu 14.23-0ubuntu0.22.04.1)
-- Dumped by pg_dump version 14.23 (Ubuntu 14.23-0ubuntu0.22.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activities (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    customer_id bigint,
    lead_id bigint,
    opportunity_id bigint,
    description text,
    scheduled_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: activities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activities_id_seq OWNED BY public.activities.id;


--
-- Name: api_keys; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.api_keys (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    key character varying(64) NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN api_keys.name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.api_keys.name IS '用途说明，如「进销存小程序服务账号」';


--
-- Name: COLUMN api_keys.key; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.api_keys.key IS '永久有效的 API Key，Bearer token 形式传入';


--
-- Name: api_keys_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.api_keys_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: api_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.api_keys_id_seq OWNED BY public.api_keys.id;


--
-- Name: app_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.app_settings (
    id bigint NOT NULL,
    key character varying(100) NOT NULL,
    value text,
    label character varying(100),
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN app_settings.key; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.app_settings.key IS '设置键，如 miniprogram_title';


--
-- Name: COLUMN app_settings.label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.app_settings.label IS '后台显示的中文说明';


--
-- Name: app_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.app_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: app_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.app_settings_id_seq OWNED BY public.app_settings.id;


--
-- Name: assistant_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.assistant_notes (
    id bigint NOT NULL,
    type character varying(255) DEFAULT 'note'::character varying NOT NULL,
    content text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT assistant_notes_type_check CHECK (((type)::text = ANY ((ARRAY['note'::character varying, 'plan'::character varying, 'ai_suggestion'::character varying])::text[])))
);


--
-- Name: assistant_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.assistant_notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: assistant_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.assistant_notes_id_seq OWNED BY public.assistant_notes.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    "position" character varying(255),
    phone character varying(255),
    email character varying(255),
    is_primary boolean DEFAULT false NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contacts_id_seq OWNED BY public.contacts.id;


--
-- Name: customers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.customers (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    company character varying(255),
    phone character varying(255),
    email character varying(255),
    address character varying(255),
    type character varying(255) DEFAULT 'individual'::character varying NOT NULL,
    source character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    assigned_to bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    gender character varying(20),
    age_group character varying(20),
    birthday date,
    city character varying(100),
    industry character varying(100),
    income_level character varying(20),
    hobbies json,
    tags json
);


--
-- Name: customers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.customers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: customers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.customers_id_seq OWNED BY public.customers.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: inventory_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_logs (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    quantity integer NOT NULL,
    before_stock integer NOT NULL,
    after_stock integer NOT NULL,
    reference_type character varying(255),
    reference_id bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: inventory_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_logs_id_seq OWNED BY public.inventory_logs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: leads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.leads (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    company character varying(255),
    phone character varying(255),
    email character varying(255),
    source character varying(255) DEFAULT 'other'::character varying NOT NULL,
    status character varying(255) DEFAULT 'new'::character varying NOT NULL,
    assigned_to bigint,
    notes text,
    last_contacted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: leads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.leads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: leads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.leads_id_seq OWNED BY public.leads.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: opportunities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.opportunities (
    id bigint NOT NULL,
    customer_id bigint,
    lead_id bigint,
    title character varying(255) NOT NULL,
    amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    stage character varying(255) DEFAULT 'prospect'::character varying NOT NULL,
    probability integer DEFAULT 0 NOT NULL,
    expected_close_date date,
    assigned_to bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: opportunities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.opportunities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: opportunities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.opportunities_id_seq OWNED BY public.opportunities.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: posts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.posts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    published boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: posts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.posts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.posts_id_seq OWNED BY public.posts.id;


--
-- Name: product_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_categories_id_seq OWNED BY public.product_categories.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    category_id bigint,
    supplier_id bigint,
    name character varying(255) NOT NULL,
    sku character varying(255) NOT NULL,
    description text,
    unit character varying(255) DEFAULT '件'::character varying NOT NULL,
    cost_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    sell_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    stock_quantity integer DEFAULT 0 NOT NULL,
    min_stock integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: purchase_order_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.purchase_order_items (
    id bigint NOT NULL,
    purchase_order_id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    total_price numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: purchase_order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchase_order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchase_order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchase_order_items_id_seq OWNED BY public.purchase_order_items.id;


--
-- Name: purchase_orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.purchase_orders (
    id bigint NOT NULL,
    order_no character varying(255) NOT NULL,
    supplier_id bigint,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    total_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    ordered_at timestamp(0) without time zone,
    received_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: purchase_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchase_orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchase_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchase_orders_id_seq OWNED BY public.purchase_orders.id;


--
-- Name: quick_action_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quick_action_items (
    id bigint NOT NULL,
    quick_action_id bigint NOT NULL,
    emoji character varying(16),
    label character varying(50) NOT NULL,
    "desc" character varying(100),
    item_type character varying(20) DEFAULT 'external'::character varying NOT NULL,
    route character varying(300),
    prompt text,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN quick_action_items."desc"; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_action_items."desc" IS '副标题说明';


--
-- Name: COLUMN quick_action_items.item_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_action_items.item_type IS 'external|route|prompt';


--
-- Name: COLUMN quick_action_items.route; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_action_items.route IS 'route/external 路径或完整 URL';


--
-- Name: COLUMN quick_action_items.prompt; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_action_items.prompt IS 'prompt 类型：发给 AI 的文字';


--
-- Name: quick_action_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quick_action_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quick_action_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quick_action_items_id_seq OWNED BY public.quick_action_items.id;


--
-- Name: quick_actions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quick_actions (
    id bigint NOT NULL,
    key character varying(50) NOT NULL,
    emoji character varying(16),
    label character varying(50) NOT NULL,
    badge character varying(20),
    action_type character varying(20) DEFAULT 'external'::character varying NOT NULL,
    prompt text,
    target_path character varying(300),
    target_title character varying(50),
    web_label character varying(50),
    enabled boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN quick_actions.key; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.key IS '按钮标识（用于样式 qa-chip-{key}）';


--
-- Name: COLUMN quick_actions.badge; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.badge IS '角标文字，空则不显示';


--
-- Name: COLUMN quick_actions.action_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.action_type IS 'external|open|menu|home|prompt|web';


--
-- Name: COLUMN quick_actions.prompt; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.prompt IS 'prompt/web 类型：发给 AI 的文字';


--
-- Name: COLUMN quick_actions.target_path; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.target_path IS 'open/external 路径或完整 URL；home 回跳路径';


--
-- Name: COLUMN quick_actions.target_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.target_title IS 'web-view 页标题';


--
-- Name: COLUMN quick_actions.web_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_actions.web_label IS 'web 类型「打开完整页」按钮文字';


--
-- Name: quick_actions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quick_actions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quick_actions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quick_actions_id_seq OWNED BY public.quick_actions.id;


--
-- Name: sales_order_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sales_order_items (
    id bigint NOT NULL,
    sales_order_id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    total_price numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sales_order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sales_order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sales_order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sales_order_items_id_seq OWNED BY public.sales_order_items.id;


--
-- Name: sales_orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sales_orders (
    id bigint NOT NULL,
    order_no character varying(255) NOT NULL,
    customer_id bigint,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    total_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    ordered_at timestamp(0) without time zone,
    shipped_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sales_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sales_orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sales_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sales_orders_id_seq OWNED BY public.sales_orders.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: suppliers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.suppliers (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    contact_person character varying(255),
    phone character varying(255),
    email character varying(255),
    address character varying(255),
    notes text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: suppliers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.suppliers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: suppliers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.suppliers_id_seq OWNED BY public.suppliers.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities ALTER COLUMN id SET DEFAULT nextval('public.activities_id_seq'::regclass);


--
-- Name: api_keys id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_keys ALTER COLUMN id SET DEFAULT nextval('public.api_keys_id_seq'::regclass);


--
-- Name: app_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_settings ALTER COLUMN id SET DEFAULT nextval('public.app_settings_id_seq'::regclass);


--
-- Name: assistant_notes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assistant_notes ALTER COLUMN id SET DEFAULT nextval('public.assistant_notes_id_seq'::regclass);


--
-- Name: contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id SET DEFAULT nextval('public.contacts_id_seq'::regclass);


--
-- Name: customers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers ALTER COLUMN id SET DEFAULT nextval('public.customers_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: inventory_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_logs ALTER COLUMN id SET DEFAULT nextval('public.inventory_logs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: leads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads ALTER COLUMN id SET DEFAULT nextval('public.leads_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: opportunities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunities ALTER COLUMN id SET DEFAULT nextval('public.opportunities_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: posts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts ALTER COLUMN id SET DEFAULT nextval('public.posts_id_seq'::regclass);


--
-- Name: product_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories ALTER COLUMN id SET DEFAULT nextval('public.product_categories_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: purchase_order_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_order_items ALTER COLUMN id SET DEFAULT nextval('public.purchase_order_items_id_seq'::regclass);


--
-- Name: purchase_orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_orders ALTER COLUMN id SET DEFAULT nextval('public.purchase_orders_id_seq'::regclass);


--
-- Name: quick_action_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_items ALTER COLUMN id SET DEFAULT nextval('public.quick_action_items_id_seq'::regclass);


--
-- Name: quick_actions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_actions ALTER COLUMN id SET DEFAULT nextval('public.quick_actions_id_seq'::regclass);


--
-- Name: sales_order_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_order_items ALTER COLUMN id SET DEFAULT nextval('public.sales_order_items_id_seq'::regclass);


--
-- Name: sales_orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_orders ALTER COLUMN id SET DEFAULT nextval('public.sales_orders_id_seq'::regclass);


--
-- Name: suppliers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers ALTER COLUMN id SET DEFAULT nextval('public.suppliers_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activities; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.activities (id, type, title, customer_id, lead_id, opportunity_id, description, scheduled_at, completed_at, user_id, created_at, updated_at) FROM stdin;
5	email	邮件跟进	3	\N	\N	\N	2026-06-13 08:01:43	\N	\N	2026-06-13 08:01:43	2026-06-13 08:01:43
4	call	电话跟进	5	\N	\N	\N	2026-06-13 08:01:33	2026-06-13 08:02:01	\N	2026-06-13 08:01:33	2026-06-13 08:02:01
6	meeting	会议 - 赵六	5	\N	\N	\N	2026-06-13 08:02:57	\N	\N	2026-06-13 08:02:57	2026-06-13 08:02:57
7	meeting	会议跟进	5	\N	\N	\N	2026-06-13 18:23:59	\N	\N	2026-06-13 18:23:59	2026-06-13 18:23:59
8	meeting	会议跟进	5	\N	\N	\N	2026-06-13 18:24:36	\N	\N	2026-06-13 18:24:36	2026-06-13 18:24:36
9	meeting	会议	5	\N	\N	\N	2026-06-13 18:31:41	\N	\N	2026-06-13 18:31:41	2026-06-13 18:31:41
10	meeting	拜访王伟	3	\N	\N	\N	2026-06-13 18:31:49	\N	\N	2026-06-13 18:31:49	2026-06-13 18:31:49
11	call	电话跟进	5	\N	\N	\N	2026-06-13 18:31:56	\N	\N	2026-06-13 18:31:56	2026-06-13 18:31:56
12	call	电话跟进	5	\N	\N	\N	2026-06-20 00:00:00	\N	\N	2026-06-13 18:32:02	2026-06-13 18:32:02
13	call	电话跟进	5	\N	\N	约了下午五点见面	2026-06-14 01:16:15	\N	\N	2026-06-14 01:16:15	2026-06-14 01:16:15
14	call	电话预约见面	3	\N	\N	下周五见面	2026-06-14 01:21:01	\N	\N	2026-06-14 01:21:01	2026-06-14 01:21:01
\.


--
-- Data for Name: api_keys; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.api_keys (id, user_id, name, key, last_used_at, created_at, updated_at) FROM stdin;
1	2	测试 Key	ak_C1LJDcqx5NtuwETI3wulJg4VwIQA6P3NZrgSLoJ2pUULB8DP	2026-06-14 05:45:24	2026-06-13 01:49:45	2026-06-14 05:45:24
\.


--
-- Data for Name: app_settings; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.app_settings (id, key, value, label, sort_order, created_at, updated_at) FROM stdin;
1	miniprogram_title	进销存及CRM	小程序顶部标题	10	2026-06-11 23:52:12	2026-06-11 23:52:12
\.


--
-- Data for Name: assistant_notes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assistant_notes (id, type, content, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
laravel-cache-livewire-rate-limiter:7d54c85b80a307a2eeabb9fca47e9302fe71d3af:timer	i:1781258802;	1781258802
laravel-cache-livewire-rate-limiter:7d54c85b80a307a2eeabb9fca47e9302fe71d3af	i:1;	1781258802
laravel-cache-livewire-rate-limiter:5d4ed5e71e8212ae09caa136392ed72477072a5a:timer	i:1781311789;	1781311789
laravel-cache-livewire-rate-limiter:5d4ed5e71e8212ae09caa136392ed72477072a5a	i:1;	1781311789
laravel-cache-8D8RfvJe6jg5LOu7	a:1:{s:11:"valid_until";i:1781311839;}	1782521499
laravel-cache-livewire-rate-limiter:ee860cfc9a1e12a37b2207bee3ae55a4b5686bdc:timer	i:1781315494;	1781315494
laravel-cache-livewire-rate-limiter:ee860cfc9a1e12a37b2207bee3ae55a4b5686bdc	i:1;	1781315494
laravel-cache-livewire-rate-limiter:600cc9277500aeb1f9c0a7f5c69d16ab11353408:timer	i:1781363003;	1781363003
laravel-cache-livewire-rate-limiter:600cc9277500aeb1f9c0a7f5c69d16ab11353408	i:1;	1781363003
laravel-cache-livewire-rate-limiter:51dc3297bd6202485ea788f1d47f7c61d12a3858:timer	i:1781408618;	1781408618
laravel-cache-livewire-rate-limiter:51dc3297bd6202485ea788f1d47f7c61d12a3858	i:1;	1781408618
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: contacts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.contacts (id, customer_id, name, "position", phone, email, is_primary, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.customers (id, name, company, phone, email, address, type, source, status, assigned_to, notes, created_at, updated_at, gender, age_group, birthday, city, industry, income_level, hobbies, tags) FROM stdin;
2	王明伟	\N	18977771111	\N	\N	individual	\N	active	\N	\N	2026-06-13 05:32:44	2026-06-13 05:32:44	\N	\N	\N	\N	\N	\N	\N	\N
3	王伟	\N	\N	\N	\N	individual	\N	active	\N	\N	2026-06-13 06:09:57	2026-06-13 06:12:48	male	26-35	\N	上海	IT	high	["\\u9ad8\\u5c14\\u592b","\\u7ea2\\u9152","\\u9493\\u9c7c"]	["VIP"]
4	李明	上海科技公司	138	\N	\N	individual	\N	active	\N	\N	2026-06-13 06:27:17	2026-06-13 06:27:17	\N	\N	\N	\N	\N	\N	\N	\N
5	赵六	\N	13811111111	\N	\N	individual	\N	active	\N	\N	2026-06-13 07:36:36	2026-06-13 07:37:06	\N	\N	\N	上海	\N	\N	\N	\N
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: inventory_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.inventory_logs (id, product_id, type, quantity, before_stock, after_stock, reference_type, reference_id, notes, created_at, updated_at) FROM stdin;
1	5	purchase	100	0	100	App\\Models\\PurchaseOrder	2	进货单 PO20260613063844	2026-06-13 06:38:44	2026-06-13 06:38:44
2	5	sale	-30	100	70	App\\Models\\SalesOrder	2	销售单 SO20260613064003	2026-06-13 06:40:03	2026-06-13 06:40:03
3	6	purchase	200	0	200	App\\Models\\PurchaseOrder	3	进货单 PO20260613073655	2026-06-13 07:36:55	2026-06-13 07:36:55
4	6	sale	-50	200	150	App\\Models\\SalesOrder	3	销售单 SO20260613073659	2026-06-13 07:36:59	2026-06-13 07:36:59
5	6	adjustment	738	150	888	\N	\N	AI助手手动调整	2026-06-13 07:37:20	2026-06-13 07:37:20
6	7	purchase	110	0	110	App\\Models\\PurchaseOrder	4	进货单 PO20260613193950	2026-06-13 19:39:50	2026-06-13 19:39:50
7	8	purchase	100	0	100	App\\Models\\PurchaseOrder	5	进货单 PO20260614041014	2026-06-14 04:10:14	2026-06-14 04:10:14
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: leads; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.leads (id, name, company, phone, email, source, status, assigned_to, notes, last_contacted_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_11_29_220023_create_personal_access_tokens_table	1
5	2025_11_29_220222_create_posts_table	1
6	2026_06_11_224312_create_product_categories_table	1
7	2026_06_11_224312_create_suppliers_table	1
8	2026_06_11_224313_create_products_table	1
9	2026_06_11_224313_create_purchase_orders_table	1
10	2026_06_11_224314_create_purchase_order_items_table	1
11	2026_06_11_224315_create_inventory_logs_table	1
12	2026_06_11_224316_create_customers_table	1
13	2026_06_11_224317_create_contacts_table	1
14	2026_06_11_224318_create_leads_table	1
15	2026_06_11_224319_create_opportunities_table	1
16	2026_06_11_224320_create_sales_orders_table	1
17	2026_06_11_224321_create_sales_order_items_table	1
18	2026_06_11_224322_create_activities_table	1
19	2026_06_12_000000_create_quick_actions_tables	2
20	2026_06_12_000001_create_app_settings_table	2
21	2026_06_13_014637_create_api_keys_table	3
22	2026_06_13_053418_add_hobbies_to_customers_table	4
23	2026_06_13_063758_make_supplier_id_nullable_on_purchase_orders	5
24	2026_06_13_063909_make_customer_id_nullable_on_sales_orders	6
25	2026_06_13_075823_make_user_id_nullable_on_activities	7
26	2026_06_14_013841_create_assistant_notes_table	8
\.


--
-- Data for Name: opportunities; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.opportunities (id, customer_id, lead_id, title, amount, stage, probability, expected_close_date, assigned_to, notes, created_at, updated_at) FROM stdin;
1	\N	\N	新项目合作	1000000.00	初步接触	10	\N	\N	\N	2026-06-12 02:33:00	2026-06-12 02:33:00
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: posts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.posts (id, user_id, title, content, published, created_at, updated_at) FROM stdin;
1	1	Voluptatem sunt sed error nobis eius corporis animi.	Distinctio vero consequatur velit voluptatum. Cupiditate nemo voluptas dolorum fuga enim nisi. Omnis temporibus aspernatur repellat ut alias laborum excepturi. Et impedit qui mollitia voluptatem deleniti sequi.\n\nCum ut ex deleniti totam repudiandae aperiam aut. Nemo est culpa ea ut enim nemo temporibus. Officia dolores esse vel accusantium. Qui exercitationem earum quam consectetur dicta aut accusamus.\n\nCulpa quia et deserunt. At ea aut voluptates voluptatibus tempore repellendus. Praesentium est vitae qui minima voluptas eos.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
2	1	Alias perferendis debitis voluptas a quia.	Iste sapiente itaque rerum atque sunt iste ea. Aliquam et et facilis. Rem aut voluptas at sunt ab.\n\nAtque sit voluptatem commodi est occaecati et. Nemo debitis odit qui voluptas ut ipsa. Sit est aut cumque voluptas suscipit rerum.\n\nUnde consequuntur nisi molestiae officia. Aut dolorum minima commodi ut. Repudiandae fugiat qui repellendus repudiandae dolor sed nisi. Hic quia omnis maxime deserunt. Voluptate placeat omnis vero et atque voluptas optio saepe.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
3	1	Ea cupiditate reprehenderit doloremque ut ut accusantium.	Quia vitae aut est ut odio aut sint. Repellendus id aut magni nulla. Accusantium natus veritatis reprehenderit. Iusto aut ab numquam vero quaerat.\n\nVelit autem reiciendis quisquam et. Asperiores in aut deserunt excepturi non eius. Eaque nemo autem tempora. Non ut quod enim omnis.\n\nDolores sit nisi ut aperiam ipsum. Facilis aut qui dolor architecto consectetur. Omnis culpa aut sed quis fuga tempora. Odio in cumque rerum illo quia unde doloremque.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
4	1	Molestiae eligendi adipisci impedit dolores consequatur.	Laudantium id rerum eos ut enim asperiores modi. Minima dicta vel atque eius dicta ex nobis. Autem corrupti ab voluptatem provident.\n\nAutem sit sunt molestiae provident sed iure. Quos a quis et quo. Harum quis aut iste est.\n\nOmnis beatae cumque a id sed dolor omnis. Sed dolorum quaerat quae. Quisquam est maiores saepe. Voluptatem est labore vero consequatur ut dolore. Dolor dolorem et voluptatibus et.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
5	1	Sed quaerat saepe et saepe quos autem illo est.	Eius impedit similique ratione sit. Alias est quos aut quidem dolorum at. Quibusdam aut accusamus cupiditate suscipit ea dolore qui. Fugit unde reiciendis est exercitationem. Eos aliquam est est ab.\n\nNon sint molestiae sequi autem sed. Ipsa ex quo doloribus consequatur sequi quo. Reiciendis delectus iste unde et sed. Omnis voluptate et et.\n\nEt culpa sint et nulla accusamus et. Et vitae optio vitae exercitationem et cum dolore quisquam. Voluptas ratione vero perferendis vitae quis.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
6	2	Iure libero est reiciendis error quod recusandae quia sequi.	Laudantium neque assumenda corrupti nisi. Provident asperiores id inventore non est exercitationem unde repellendus. Non dolores quas aut alias impedit voluptas. Et quia beatae vero iusto et molestiae atque.\n\nEsse itaque rerum blanditiis est quod voluptatibus explicabo. Rerum aliquid dolor labore et molestias. Reiciendis molestiae tenetur a dolores. Aut culpa impedit est numquam perferendis laudantium accusamus.\n\nQui similique accusamus aliquid ipsa dolore sit quidem. Pariatur animi possimus cumque cum ut ducimus. Corporis et nihil earum. Nulla neque velit qui modi dolor porro consequatur.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
7	2	Facilis quo assumenda ut in ducimus.	Ut quis et voluptas ut. Maiores dolor fugit voluptates sed aperiam et. Non nesciunt explicabo et. Molestias perferendis illum ipsam sequi nam.\n\nEa molestiae aperiam et placeat. Eligendi dolores eius quas. Deserunt adipisci minima omnis aut neque ut tenetur voluptatum. Sint aperiam autem consequatur recusandae quia dolore.\n\nEa explicabo omnis dolor magni nisi unde ducimus repellendus. Cupiditate esse eos omnis voluptatum qui vel ut omnis. Adipisci mollitia id porro maiores sunt et aut. Aut quam sunt non ut et sapiente.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
8	2	Nulla sunt illum suscipit et error et.	Quos quidem velit ab. Voluptatem sequi error nesciunt molestias. Et voluptate dolorem facere ullam unde quibusdam id. Sit vitae sint facilis iste ab commodi accusamus.\n\nHic quae earum harum dolor temporibus quaerat. Nihil sint quis aut consectetur consequatur autem. Officia iusto et facere voluptatibus.\n\nSint suscipit iure dolore qui nostrum. Non amet ea excepturi excepturi. Eum magni vel officiis et perferendis ab vel sint. Sit ullam hic voluptate blanditiis rerum. Qui qui porro modi explicabo dolorum.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
9	2	Minima illum laudantium harum aperiam dolores non.	Molestiae sequi pariatur reiciendis a quia. Ut quaerat eius quidem omnis et. In quidem quos ullam quia fugiat.\n\nNulla omnis velit in odit et consequatur. Sint commodi natus sint molestiae veniam in dignissimos. Perferendis numquam consequatur in voluptatem nihil ea dolor. Magnam aperiam ab provident repudiandae ad.\n\nVoluptatem ullam saepe ducimus vitae facilis. Illo quas quisquam est est voluptates. Veniam voluptas possimus saepe corporis error aliquid id ducimus.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
10	3	Aliquam ipsa tempore a praesentium aut.	Ipsa laudantium suscipit adipisci non illo enim. Odit ipsa nisi et. Eius quibusdam dolores perspiciatis. Eveniet veritatis dicta error eligendi voluptatem voluptas illum.\n\nEst enim et dolore accusantium amet. Est corrupti atque et voluptatum beatae eum doloremque. Corrupti dignissimos tempora quaerat consequatur perspiciatis. Repellat qui a quis molestiae et quaerat voluptate.\n\nCommodi sed aut aspernatur rerum velit debitis aut. Officia enim expedita sed voluptatem ab deserunt. Laboriosam facere eaque sed molestiae. Ipsam nam iste et atque rerum velit quo.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
11	3	Doloremque enim nobis quasi ab omnis.	Est incidunt adipisci ratione totam qui quasi. Et quaerat quas odio explicabo vitae possimus aliquam. Occaecati aut ut dolorem hic quidem aut.\n\nMagnam quo ut non qui qui ut atque aut. Molestiae recusandae rerum ab qui beatae quo. Ut recusandae id molestias voluptates optio.\n\nAmet dolorem aut voluptatem ut inventore ullam. Omnis voluptatem repellat blanditiis ratione earum aut libero voluptatum. Autem nulla assumenda itaque ipsa saepe eum. Omnis id non rem aut dolorem. Velit nam aut itaque veritatis consequatur.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
12	3	Ut delectus qui voluptatem dolor adipisci temporibus vitae.	Quo illo repellendus ut assumenda omnis. Vel magni et et ea voluptas et. Placeat eum optio voluptatibus ut sequi expedita. Est vel consequatur esse quisquam odio.\n\nAd aut sit ut quia ducimus dolorem sapiente. Quia placeat quis quos sed magnam ut. Rem aspernatur eum et quas veritatis inventore.\n\nEsse adipisci voluptas voluptatem in labore minima rem est. Dolores officiis et quo maiores natus. Occaecati enim illum voluptatibus voluptatibus omnis eos.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
13	3	Nihil qui molestias sequi dolorem perspiciatis magnam adipisci.	Ab qui beatae libero quibusdam velit. Numquam labore doloribus dolore sit. Commodi ipsam explicabo numquam eos reiciendis laudantium qui.\n\nTenetur eos ut numquam labore odit. Quia dolor sit non officiis aut nostrum autem. Magnam laborum voluptas blanditiis inventore corporis amet.\n\nEligendi explicabo ad et inventore. Non voluptatem nisi deserunt dignissimos accusantium.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
14	4	Officiis ad reiciendis sed molestiae sit placeat.	Fuga nesciunt voluptas molestias eum sunt aspernatur labore ut. Quod doloremque dolores in itaque et non omnis. Deleniti ut commodi vero hic. Non error illo dolorem et illo. Qui eveniet debitis aliquid rem vero cumque.\n\nTempore et odio est fuga ut. Quos tenetur culpa repellendus inventore deleniti necessitatibus est. Quia est cupiditate quod eos mollitia.\n\nAtque sed occaecati facilis. Nobis totam officiis repellendus fuga enim earum itaque. Nihil est iusto dolorum aut.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
15	4	Eaque debitis vel totam perspiciatis atque qui.	Repudiandae odit esse dolor mollitia dolorem. Omnis at quasi aut consequatur voluptatem veniam.\n\nRepudiandae dolor fugit quod et repellendus dolor et alias. Reiciendis beatae ducimus explicabo accusantium atque. Eum non consequatur eos vitae ut voluptas. Minima quia qui rerum. Vel ut ipsa amet voluptatem est.\n\nQuia magni sapiente qui. Ad et voluptatum qui voluptatem. Qui ad aut amet sint occaecati vel. At magnam rerum minima.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
16	4	Enim qui ratione distinctio dicta quas omnis.	Ab quo temporibus vero. Corrupti incidunt quod voluptates qui voluptatibus. Et eius aut aut quia.\n\nAliquam voluptatem illo eaque porro sint quis et. Repellendus vel pariatur veniam ipsa at et dolor.\n\nConsequatur nesciunt beatae consectetur hic perferendis autem corrupti voluptatem. Ipsa voluptatem praesentium qui illum sapiente. Voluptatem ipsam similique qui. Id blanditiis quis alias veritatis sunt molestiae.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
17	5	Consequatur beatae cupiditate iure dolore eum a et.	Saepe libero fuga eos quasi harum dolores voluptates. Aut unde et ut explicabo sed aut. Animi laudantium et consequatur. Aut ut libero itaque necessitatibus.\n\nEt non non rerum voluptatem possimus. Animi et sed cupiditate doloremque ab quidem.\n\nAd consequatur et voluptatem commodi aut. Nam laborum esse quaerat laborum quia magni impedit. Doloremque ex qui recusandae.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
18	5	Quas ut libero pariatur et consequatur itaque.	Commodi alias debitis ipsam aut fugit tempore nemo dolorem. Ipsam accusamus omnis cumque suscipit.\n\nImpedit mollitia esse dolor at. Earum quisquam labore officia. Voluptas optio veritatis doloremque incidunt omnis. Veniam qui et architecto ut et enim.\n\nEos commodi ut libero at necessitatibus inventore. Ut fugiat sed aliquam sint ipsam at. Qui dolores ut saepe itaque exercitationem pariatur pariatur quibusdam.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
19	5	Et occaecati suscipit eos molestias porro.	Magni esse quisquam consequatur blanditiis fugit rerum. Minima ipsam et reprehenderit necessitatibus illo vel temporibus dolor. Dolores est officia ex alias eos aut error.\n\nConsequatur incidunt neque ipsam eum at natus consequuntur. Aperiam hic facilis amet quia sapiente voluptatum maiores. Qui molestiae commodi placeat recusandae ea et. Est aliquid suscipit libero aspernatur id occaecati cumque laborum.\n\nDignissimos repellendus quidem est sit aut repudiandae distinctio dolore. Aut voluptates mollitia voluptate omnis libero debitis voluptatem. Cumque voluptatem ducimus quam sunt repudiandae velit hic suscipit.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
20	5	Officiis reprehenderit omnis assumenda quidem.	Et hic animi ipsa non corrupti alias aut. Sed labore fugit doloribus nesciunt aut perferendis. Maiores ut provident nulla. Maiores deleniti tempora adipisci perspiciatis ipsum consequatur.\n\nIllum magni perferendis suscipit praesentium. Sint aliquam hic omnis ut occaecati beatae.\n\nSunt dignissimos sed odit et. Esse ut officiis rerum blanditiis sapiente commodi. Ipsa nihil magni voluptatem et modi animi molestias.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
21	6	Sint eligendi nihil ipsa fugit alias mollitia.	Dolores voluptatem voluptatem quasi non. Culpa illum quia autem ullam porro. Eos perspiciatis deleniti et ipsa maxime. Et quod quo ducimus consequuntur numquam et qui.\n\nImpedit et soluta rem quaerat tempora. Corporis quibusdam delectus quod suscipit. Aut amet cupiditate quia sint earum voluptas. Rem quam magni sed.\n\nQuia facere rerum id et repellendus nulla perspiciatis. Consequatur quae minus velit dolores qui nisi reprehenderit. Corporis ut non dolorem quam similique aut animi. Aut molestiae quas est maxime occaecati quas earum ut. Sed sunt est provident.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
22	6	Ducimus dolores autem sunt voluptatum sit nostrum facere et.	Fugit sint iusto inventore animi. Hic corporis quia inventore et incidunt aut maiores. Voluptatem sed dignissimos molestiae blanditiis deserunt quisquam nostrum.\n\nQuasi unde at dolorum nostrum. Quis itaque distinctio eligendi maxime. Quae doloribus quisquam quia error rerum aspernatur consequatur voluptatem. Omnis ullam qui maxime aut a.\n\nVoluptate sed quasi sit dolorum. Sed accusamus distinctio voluptatem dolor at. Iste error totam quidem.	t	2026-06-11 23:14:38	2026-06-11 23:14:38
23	6	Et eligendi magnam veritatis doloribus quia odio.	Pariatur maiores et voluptatem quis voluptas architecto veniam molestiae. In deserunt dolorum qui qui dolores facere. Eum voluptatem earum nostrum quas natus qui.\n\nA culpa officia praesentium sit sint. Est accusantium pariatur nam eveniet. Quibusdam cumque consequatur voluptatem. Pariatur aut autem quia non et.\n\nIste quaerat omnis provident iusto accusantium consequatur. Possimus nisi qui molestiae. Magni veniam facere facere natus ratione.	f	2026-06-11 23:14:38	2026-06-11 23:14:38
\.


--
-- Data for Name: product_categories; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.product_categories (id, name, description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.products (id, category_id, supplier_id, name, sku, description, unit, cost_price, sell_price, stock_quantity, min_stock, status, created_at, updated_at) FROM stdin;
1	\N	\N	毛巾	123	\N	件	0.00	0.00	0	0	active	2026-06-13 00:51:10	2026-06-13 00:51:10
3	\N	\N	水杯	P1781316134	\N	个	0.00	8.00	1000	0	active	2026-06-13 02:02:14	2026-06-13 02:02:34
5	\N	\N	测试杯子	P1781332724	\N	个	5.00	5.00	70	0	active	2026-06-13 06:38:44	2026-06-13 06:40:03
6	\N	\N	橙子	P1781336192	\N	斤	0.00	5.00	888	0	active	2026-06-13 07:36:32	2026-06-13 07:37:20
7	\N	\N	袜子	P1781379590	\N	个	0.00	0.00	110	0	active	2026-06-13 19:39:50	2026-06-13 19:39:50
8	\N	\N	苹果	P1781410214	\N	个	0.00	0.00	100	0	active	2026-06-14 04:10:14	2026-06-14 04:10:14
\.


--
-- Data for Name: purchase_order_items; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.purchase_order_items (id, purchase_order_id, product_id, quantity, unit_price, total_price, created_at, updated_at) FROM stdin;
1	2	5	100	5.00	500.00	2026-06-13 06:38:44	2026-06-13 06:38:44
2	3	6	200	2.00	400.00	2026-06-13 07:36:55	2026-06-13 07:36:55
3	4	7	110	0.00	0.00	2026-06-13 19:39:50	2026-06-13 19:39:50
4	5	8	100	0.00	0.00	2026-06-14 04:10:14	2026-06-14 04:10:14
\.


--
-- Data for Name: purchase_orders; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.purchase_orders (id, order_no, supplier_id, status, total_amount, notes, ordered_at, received_at, created_at, updated_at) FROM stdin;
2	PO20260613063844	\N	received	500.00	\N	2026-06-13 06:38:44	2026-06-13 06:38:44	2026-06-13 06:38:44	2026-06-13 06:38:44
3	PO20260613073655	\N	received	400.00	\N	2026-06-13 07:36:55	2026-06-13 07:36:55	2026-06-13 07:36:55	2026-06-13 07:36:55
4	PO20260613193950	\N	received	0.00	\N	2026-06-13 19:39:50	2026-06-13 19:39:50	2026-06-13 19:39:50	2026-06-13 19:39:50
5	PO20260614041014	\N	received	0.00	\N	2026-06-14 04:10:14	2026-06-14 04:10:14	2026-06-14 04:10:14	2026-06-14 04:10:14
\.


--
-- Data for Name: quick_action_items; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.quick_action_items (id, quick_action_id, emoji, label, "desc", item_type, route, prompt, sort_order, created_at, updated_at) FROM stdin;
1	5	🧑	客户	客户档案	route	/pages/report/report?path=%2Fcustomers&title=%E5%AE%A2%E6%88%B7%E7%AE%A1%E7%90%86	\N	10	2026-06-11 23:52:12	2026-06-12 02:20:45
2	5	🎯	线索	销售线索	route	/pages/report/report?path=%2Fleads&title=%E9%94%80%E5%94%AE%E7%BA%BF%E7%B4%A2	\N	20	2026-06-11 23:52:12	2026-06-12 02:20:45
3	5	💼	商机	销售机会	route	/pages/report/report?path=%2Fopportunities&title=%E9%94%80%E5%94%AE%E5%95%86%E6%9C%BA	\N	30	2026-06-11 23:52:12	2026-06-12 02:20:45
\.


--
-- Data for Name: quick_actions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.quick_actions (id, key, emoji, label, badge, action_type, prompt, target_path, target_title, web_label, enabled, sort_order, created_at, updated_at) FROM stdin;
1	home	🏠	返回主页	\N	home	\N	/pages/industry/industry	\N	\N	t	1	2026-06-11 23:52:12	2026-06-11 23:52:12
2	products	📦	库存	\N	open	\N	/products	商品管理	\N	t	10	2026-06-11 23:52:12	2026-06-14 01:29:49
3	sales-orders	🧾	销售	\N	open	\N	/sales-orders	销售订单	\N	t	20	2026-06-11 23:52:12	2026-06-14 01:29:49
4	purchase-orders	🚚	采购	\N	open	\N	/purchase-orders	采购订单	\N	t	30	2026-06-11 23:52:12	2026-06-14 01:29:49
5	crm	👥	客户管理	\N	menu	\N	\N	\N	\N	t	40	2026-06-11 23:52:12	2026-06-14 01:30:33
6	assistant	🤖	个人助理	\N	open	\N	/assistant	\N	\N	t	50	2026-06-14 01:34:08	2026-06-14 01:45:12
\.


--
-- Data for Name: sales_order_items; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sales_order_items (id, sales_order_id, product_id, quantity, unit_price, total_price, created_at, updated_at) FROM stdin;
1	2	5	30	9.00	270.00	2026-06-13 06:40:03	2026-06-13 06:40:03
2	3	6	50	4.00	200.00	2026-06-13 07:36:59	2026-06-13 07:36:59
\.


--
-- Data for Name: sales_orders; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sales_orders (id, order_no, customer_id, status, total_amount, notes, ordered_at, shipped_at, completed_at, created_at, updated_at) FROM stdin;
2	SO20260613064003	\N	completed	270.00	\N	2026-06-13 06:40:03	\N	2026-06-13 06:40:03	2026-06-13 06:40:03	2026-06-13 06:40:03
3	SO20260613073659	\N	completed	200.00	\N	2026-06-13 07:36:59	\N	2026-06-13 07:36:59	2026-06-13 07:36:59	2026-06-13 07:36:59
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
rDYzC0MzntfiaCtJFDf0mQ45ru8HCyaEaM9F2jAr	\N	127.0.0.1	curl/7.81.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiY3BxTzRyUmc1Sm1zMTNXTXdYekhEbE1wa09NU1FHWTNsZmNXc0N5YSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1781258471
8bqyTaAFp8tyfve0Q09wcJGAnIgnxXvDbocI5dkG	\N	127.0.0.1	curl/7.81.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiWDQ0Yk43cHRUdGlaemlueXVNQ3J2bVozSEM4a1hTcTdNVVFQYmdxTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODMwMS9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1781258502
m3h1M2rBaark10p9VhCWlbznjleSrGq0hZsNebrJ	1	162.159.98.56	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo2OntzOjY6Il90b2tlbiI7czo0MDoiMjNycWt3b2xQMzdobmp6ZXdwUklaZ3ZrWnRERW1tSkFSckRCSk9MaSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMyOiJodHRwczovL2FwcDIueGluZ2tlODg4LmNvbS9hZG1pbiI7czo1OiJyb3V0ZSI7czozMDoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZTQ0MGI2NzEwZDgwN2FmMjliNzEyMjE2ZjI0ODdkYTA1MWYxNWViM2M0YzYzNjIwMWJhYTgzMjBkYWUxNWMxYSI7fQ==	1781258753
YZvSfny2fbgaSHuNCo9l0OiqypohKw5z4B1Rjh2r	\N	162.158.178.4	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSlBkWGg4Z3pGM0tPZmE4bGFZUTdTWm9sRXZGYkNFYjlnQ05YVFdQaCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0MToiaHR0cHM6Ly9hcHAyLnhpbmdrZTg4OC5jb20vYWRtaW4vYXBpLWtleXMiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozODoiaHR0cHM6Ly9hcHAyLnhpbmdrZTg4OC5jb20vYWRtaW4vbG9naW4iO3M6NToicm91dGUiO3M6MjU6ImZpbGFtZW50LmFkbWluLmF1dGgubG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1781327917
BMfjEEBbJBQtkQ1Zw6F4gynEOtbzgeb1SaIY2SgM	\N	162.159.98.57	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiTWZ4RkRPaFpHWllmZ3ZyaGF0dzdFRTR1b0EyWHA3TGNzREs2aHM2RiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzg6Imh0dHBzOi8vYXBwMi54aW5na2U4ODguY29tL2FkbWluL2xvZ2luIjtzOjU6InJvdXRlIjtzOjI1OiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1781362944
GDocywT0NPeaYurOBOVPwfQUtM02RdV0iaUINtFn	\N	42.114.27.179	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiZlE2UFlzWkFnMm12MFYwOW8wZ01ObmNyaDBhT1FZbEdjaTFhcE80UyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vNDcuODMuOS4xNzMvYWRtaW4vbG9naW4iO3M6NToicm91dGUiO3M6MjU6ImZpbGFtZW50LmFkbWluLmF1dGgubG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1781383081
QGqixdjhEsYgrukEsw7RzupoWLoRPFBv1rdy96bw	\N	172.70.206.67	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTERqWVFKWk1rQkJBd1R2NzZzajFTN3Q4UlBoeFNEUXR0ZnBqSWhLaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzg6Imh0dHBzOi8vYXBwMi54aW5na2U4ODguY29tL2FkbWluL2xvZ2luIjtzOjU6InJvdXRlIjtzOjI1OiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMjoiaHR0cHM6Ly9hcHAyLnhpbmdrZTg4OC5jb20vYWRtaW4iO319	1781412040
6rnp06vBk0CrZJe0Iw4FQupP0pRzQEPjLUacvTN9	1	172.69.72.154	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo3OntzOjY6Il90b2tlbiI7czo0MDoiZUFtdEM5V0ZXMTJnUFNDdjNaZ3pndldUUU5DbkI3bjJjcHFNM01PVyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vYXBwMi54aW5na2U4ODguY29tL2FkbWluL2FwaS1rZXlzIjtzOjU6InJvdXRlIjtzOjM5OiJmaWxhbWVudC5hZG1pbi5yZXNvdXJjZXMuYXBpLWtleXMuaW5kZXgiO31zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZTQ0MGI2NzEwZDgwN2FmMjliNzEyMjE2ZjI0ODdkYTA1MWYxNWViM2M0YzYzNjIwMWJhYTgzMjBkYWUxNWMxYSI7czo2OiJ0YWJsZXMiO2E6MTp7czo0MDoiNWU0MGY1Yzk0MjNlOTc1NjMxYjMwNjI3NDVjZDkwZTRfY29sdW1ucyI7YTo1OntpOjA7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6OToidXNlci5uYW1lIjtzOjU6ImxhYmVsIjtzOjY6IueUqOaItyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NDoibmFtZSI7czo1OiJsYWJlbCI7czoxMjoi55So6YCU6K+05piOIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MjthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czozOiJrZXkiO3M6NToibGFiZWwiO3M6MTU6Iktlee+8iOWJjee8gO+8iSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjM7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTI6Imxhc3RfdXNlZF9hdCI7czo1OiJsYWJlbCI7czoxMjoi5pyA5ZCO5L2/55SoIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6NDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMDoiY3JlYXRlZF9hdCI7czo1OiJsYWJlbCI7czoxMjoi5Yib5bu65pe26Ze0IjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fX19fQ==	1781316988
\.


--
-- Data for Name: suppliers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.suppliers (id, name, contact_person, phone, email, address, notes, status, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at) FROM stdin;
2	Vicenta Harvey	bnitzsche@example.com	2026-06-11 23:14:38	$2y$12$Bha8UYfg7rGgYnFGNiKIZuMuIucGwmI0RrKhWlOWJEvH8vYGhqqdW	gnVGvETgkb	2026-06-11 23:14:38	2026-06-11 23:14:38
3	Willard Turner	eulalia.kuphal@example.net	2026-06-11 23:14:38	$2y$12$Bha8UYfg7rGgYnFGNiKIZuMuIucGwmI0RrKhWlOWJEvH8vYGhqqdW	8eHwkvbDiM	2026-06-11 23:14:38	2026-06-11 23:14:38
4	Eva Reinger DDS	nolan.christa@example.org	2026-06-11 23:14:38	$2y$12$Bha8UYfg7rGgYnFGNiKIZuMuIucGwmI0RrKhWlOWJEvH8vYGhqqdW	8kIiYZRd9J	2026-06-11 23:14:38	2026-06-11 23:14:38
5	Forest Haley PhD	matteo.ratke@example.net	2026-06-11 23:14:38	$2y$12$Bha8UYfg7rGgYnFGNiKIZuMuIucGwmI0RrKhWlOWJEvH8vYGhqqdW	QDW1F7zx49	2026-06-11 23:14:38	2026-06-11 23:14:38
6	Colleen Kris	jaskolski.braxton@example.net	2026-06-11 23:14:38	$2y$12$Bha8UYfg7rGgYnFGNiKIZuMuIucGwmI0RrKhWlOWJEvH8vYGhqqdW	aFPJnaFKxW	2026-06-11 23:14:38	2026-06-11 23:14:38
1	Demo User	demo@example.com	2026-06-11 23:14:38	$2y$12$Bha8UYfg7rGgYnFGNiKIZuMuIucGwmI0RrKhWlOWJEvH8vYGhqqdW	RQaVL5hHVdmOHlDhvzZwhM6vyn4XN1pGImBsJxG25yASDQDNMrCmhBaLqX17	2026-06-11 23:14:38	2026-06-11 23:14:38
\.


--
-- Name: activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.activities_id_seq', 14, true);


--
-- Name: api_keys_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.api_keys_id_seq', 1, true);


--
-- Name: app_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.app_settings_id_seq', 1, true);


--
-- Name: assistant_notes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.assistant_notes_id_seq', 2, true);


--
-- Name: contacts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.contacts_id_seq', 1, false);


--
-- Name: customers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.customers_id_seq', 5, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: inventory_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.inventory_logs_id_seq', 7, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: leads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.leads_id_seq', 1, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 26, true);


--
-- Name: opportunities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.opportunities_id_seq', 2, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 1, false);


--
-- Name: posts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.posts_id_seq', 23, true);


--
-- Name: product_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_categories_id_seq', 1, false);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_id_seq', 8, true);


--
-- Name: purchase_order_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.purchase_order_items_id_seq', 4, true);


--
-- Name: purchase_orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.purchase_orders_id_seq', 5, true);


--
-- Name: quick_action_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.quick_action_items_id_seq', 3, true);


--
-- Name: quick_actions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.quick_actions_id_seq', 6, true);


--
-- Name: sales_order_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sales_order_items_id_seq', 2, true);


--
-- Name: sales_orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sales_orders_id_seq', 3, true);


--
-- Name: suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.suppliers_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_pkey PRIMARY KEY (id);


--
-- Name: api_keys api_keys_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_key_unique UNIQUE (key);


--
-- Name: api_keys api_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_pkey PRIMARY KEY (id);


--
-- Name: app_settings app_settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_settings
    ADD CONSTRAINT app_settings_key_unique UNIQUE (key);


--
-- Name: app_settings app_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_settings
    ADD CONSTRAINT app_settings_pkey PRIMARY KEY (id);


--
-- Name: assistant_notes assistant_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assistant_notes
    ADD CONSTRAINT assistant_notes_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id);


--
-- Name: customers customers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: inventory_logs inventory_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_logs
    ADD CONSTRAINT inventory_logs_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: leads leads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: opportunities opportunities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunities
    ADD CONSTRAINT opportunities_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: posts posts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_pkey PRIMARY KEY (id);


--
-- Name: product_categories product_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories
    ADD CONSTRAINT product_categories_pkey PRIMARY KEY (id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_sku_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_sku_unique UNIQUE (sku);


--
-- Name: purchase_order_items purchase_order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_order_items
    ADD CONSTRAINT purchase_order_items_pkey PRIMARY KEY (id);


--
-- Name: purchase_orders purchase_orders_order_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_orders
    ADD CONSTRAINT purchase_orders_order_no_unique UNIQUE (order_no);


--
-- Name: purchase_orders purchase_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_orders
    ADD CONSTRAINT purchase_orders_pkey PRIMARY KEY (id);


--
-- Name: quick_action_items quick_action_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_items
    ADD CONSTRAINT quick_action_items_pkey PRIMARY KEY (id);


--
-- Name: quick_actions quick_actions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_actions
    ADD CONSTRAINT quick_actions_pkey PRIMARY KEY (id);


--
-- Name: sales_order_items sales_order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_order_items
    ADD CONSTRAINT sales_order_items_pkey PRIMARY KEY (id);


--
-- Name: sales_orders sales_orders_order_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_orders
    ADD CONSTRAINT sales_orders_order_no_unique UNIQUE (order_no);


--
-- Name: sales_orders sales_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_orders
    ADD CONSTRAINT sales_orders_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: suppliers suppliers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: api_keys_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX api_keys_key_index ON public.api_keys USING btree (key);


--
-- Name: inventory_logs_reference_type_reference_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_logs_reference_type_reference_id_index ON public.inventory_logs USING btree (reference_type, reference_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: quick_action_items_quick_action_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quick_action_items_quick_action_id_sort_order_index ON public.quick_action_items USING btree (quick_action_id, sort_order);


--
-- Name: quick_actions_enabled_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quick_actions_enabled_sort_order_index ON public.quick_actions USING btree (enabled, sort_order);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: activities activities_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE SET NULL;


--
-- Name: activities activities_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE SET NULL;


--
-- Name: activities activities_opportunity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_opportunity_id_foreign FOREIGN KEY (opportunity_id) REFERENCES public.opportunities(id) ON DELETE SET NULL;


--
-- Name: activities activities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: api_keys api_keys_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: contacts contacts_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE CASCADE;


--
-- Name: customers customers_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventory_logs inventory_logs_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_logs
    ADD CONSTRAINT inventory_logs_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id);


--
-- Name: leads leads_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: opportunities opportunities_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunities
    ADD CONSTRAINT opportunities_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: opportunities opportunities_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunities
    ADD CONSTRAINT opportunities_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE SET NULL;


--
-- Name: opportunities opportunities_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunities
    ADD CONSTRAINT opportunities_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE SET NULL;


--
-- Name: posts posts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.product_categories(id) ON DELETE SET NULL;


--
-- Name: products products_supplier_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id) ON DELETE SET NULL;


--
-- Name: purchase_order_items purchase_order_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_order_items
    ADD CONSTRAINT purchase_order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id);


--
-- Name: purchase_order_items purchase_order_items_purchase_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_order_items
    ADD CONSTRAINT purchase_order_items_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES public.purchase_orders(id) ON DELETE CASCADE;


--
-- Name: purchase_orders purchase_orders_supplier_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_orders
    ADD CONSTRAINT purchase_orders_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id);


--
-- Name: quick_action_items quick_action_items_quick_action_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_items
    ADD CONSTRAINT quick_action_items_quick_action_id_foreign FOREIGN KEY (quick_action_id) REFERENCES public.quick_actions(id) ON DELETE CASCADE;


--
-- Name: sales_order_items sales_order_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_order_items
    ADD CONSTRAINT sales_order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id);


--
-- Name: sales_order_items sales_order_items_sales_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_order_items
    ADD CONSTRAINT sales_order_items_sales_order_id_foreign FOREIGN KEY (sales_order_id) REFERENCES public.sales_orders(id) ON DELETE CASCADE;


--
-- Name: sales_orders sales_orders_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales_orders
    ADD CONSTRAINT sales_orders_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id);


--
-- PostgreSQL database dump complete
--

\unrestrict JeTmCV3EmBrcQ2aPaR6hBUBWUsYuS3OBzsIDABqyuyzmw6LLhYsCjeV7Yd5ZSCq


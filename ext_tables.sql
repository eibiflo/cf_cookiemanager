CREATE TABLE tx_cfcookiemanager_domain_model_cookie (
	name varchar(255) NOT NULL DEFAULT '',
	http_only int(11) NOT NULL DEFAULT '0',
	domain varchar(255) NOT NULL DEFAULT '',
	secure varchar(255) NOT NULL DEFAULT '',
	path varchar(255) NOT NULL DEFAULT '',
	description text NOT NULL DEFAULT '',
	expiry int(11) NOT NULL DEFAULT '0',
	is_regex smallint(1) unsigned NOT NULL DEFAULT '0',
	service_identifier varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE tx_cfcookiemanager_domain_model_cookieservice (
	name varchar(255) NOT NULL DEFAULT '',
	identifier varchar(255) NOT NULL DEFAULT '',
	description text NOT NULL DEFAULT '',
	provider varchar(255) NOT NULL DEFAULT '',
	opt_in_code text NOT NULL DEFAULT '',
	opt_out_code text NOT NULL DEFAULT '',
	fallback_code text NOT NULL DEFAULT '',
	dsgvo_link varchar(255) NOT NULL DEFAULT '',
	iframe_embed_url text NOT NULL DEFAULT '',
	iframe_thumbnail_url text NOT NULL DEFAULT '',
	iframe_notice varchar(255) NOT NULL DEFAULT '',
	iframe_load_btn varchar(255) NOT NULL DEFAULT '',
	iframe_load_all_btn varchar(255) NOT NULL DEFAULT '',
	category_suggestion varchar(255) NOT NULL DEFAULT '',
	cookie int(11) unsigned NOT NULL DEFAULT '0',
	contentoverride int(11) unsigned DEFAULT '0',
	external_scripts int(11) unsigned NOT NULL DEFAULT '0',
	variable_priovider int(11) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_cfcookiemanager_domain_model_cookiecartegories (
	title varchar(255) NOT NULL DEFAULT '',
	description text NOT NULL DEFAULT '',
	identifier varchar(255) NOT NULL DEFAULT '',
	is_required smallint(1) unsigned NOT NULL DEFAULT '0',
	cookie_services int(11) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_cfcookiemanager_domain_model_conntentoverride (
	name varchar(255) NOT NULL DEFAULT '',
	contentlink text NOT NULL DEFAULT ''
);

CREATE TABLE tx_cfcookiemanager_domain_model_cookiefrontend (
	identifier varchar(255) NOT NULL DEFAULT '',
	name varchar(255) NOT NULL DEFAULT '',
	enabled smallint(1) unsigned NOT NULL DEFAULT '0',
	title_consent_modal varchar(255) NOT NULL DEFAULT '',
	description_consent_modal text,
    revision_text text,
	primary_btn_text_consent_modal varchar(255) NOT NULL DEFAULT '',
	primary_btn_role_consent_modal varchar(255) NOT NULL DEFAULT '',
	secondary_btn_text_consent_modal varchar(255) NOT NULL DEFAULT '',
	secondary_btn_role_consent_modal varchar(255) NOT NULL DEFAULT '',
    tertiary_btn_role_consent_modal varchar(255) NOT NULL DEFAULT '',
    tertiary_btn_text_consent_modal varchar(255) NOT NULL DEFAULT '',
	title_settings varchar(255) NOT NULL DEFAULT '',
	save_btn_settings varchar(255) NOT NULL DEFAULT '',
	accept_all_btn_settings varchar(255) NOT NULL DEFAULT '',
	reject_all_btn_settings varchar(255) NOT NULL DEFAULT '',
	close_btn_settings varchar(255) NOT NULL DEFAULT '',
	col1_header_settings varchar(255) NOT NULL DEFAULT '',
	col2_header_settings varchar(255) NOT NULL DEFAULT '',
	col3_header_settings varchar(255) NOT NULL DEFAULT '',
	blocks_title varchar(255) NOT NULL DEFAULT '',
	blocks_description text,
	custombutton smallint(1) unsigned NOT NULL DEFAULT '0',
	custom_button_html text NOT NULL DEFAULT '',
	in_line_execution smallint(1) unsigned NOT NULL DEFAULT '0',
	layout_consent_modal varchar(255) NOT NULL DEFAULT '',
	layout_settings varchar(255) NOT NULL DEFAULT '',
	position_consent_modal varchar(255) NOT NULL DEFAULT '',
	position_settings varchar(255) NOT NULL DEFAULT '',
	transition_consent_modal varchar(255) NOT NULL DEFAULT '',
	transition_settings varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE tx_cfcookiemanager_domain_model_externalscripts (
	cookieservice int(11) unsigned DEFAULT '0' NOT NULL,
	name varchar(255) NOT NULL DEFAULT '',
	link varchar(255) NOT NULL DEFAULT '',
	async smallint(1) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_cfcookiemanager_domain_model_variables (
	cookieservice int(11) unsigned DEFAULT '0' NOT NULL,
	name varchar(255) NOT NULL DEFAULT '',
	identifier varchar(255) NOT NULL DEFAULT '',
	value varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE tx_cfcookiemanager_domain_model_scans (
	domain varchar(255) NOT NULL DEFAULT '',
	click_consent smallint(1) unsigned NOT NULL DEFAULT '0',
	consent_xpath varchar(255) NOT NULL DEFAULT '',
	provider text NOT NULL DEFAULT '',
	unknownprovider text NOT NULL DEFAULT '',
	cookies text NOT NULL DEFAULT '',
	scanned_sites varchar(255) NOT NULL DEFAULT '',
	max_sites varchar(255) NOT NULL DEFAULT '',
	identifier varchar(255) NOT NULL DEFAULT '',
	status varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE `tx_cfcookiemanager_domain_model_tracking` (
   `uid` int(11) NOT NULL auto_increment,
   `pid`  int(11) DEFAULT '0' NOT NULL,
   `consent_page`  int(11) DEFAULT '0' NOT NULL,
   `consent_type` varchar(10) NOT NULL,
   `consent_date` int(11) DEFAULT '0' NOT NULL,
   `language_code` varchar(10) NOT NULL,
   `navigator` int(11) DEFAULT '0' NOT NULL,
   `referrer` varchar(255) NOT NULL,
   `user_agent` varchar(255) NOT NULL,

   PRIMARY KEY (`uid`)
);
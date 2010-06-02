<?php
// File generated with install.php

Config::set('db.host', 'localhost');

Config::set('db.user', 'root');

Config::set('db.pass', 'root');

Config::set('db.schema', 'issue-tracker');

Config::set('site.public_host', '192.168.59.99');

Config::set('site.title', 'Issues Tracker');

Config::set('site.timezone', 'Europe/Athens');

Config::set('site.google_analytics', '');

Config::set('site.authn.type', 'ldap');

Config::set('site.authn.ldap_url', 'ldap://192.168.59.110');

Config::set('site.authn.ldap_basedn', 'DC=kmfa-lab,DC=net');

Config::set('site.authn.ldap_default_domain', 'kmfa-lab.net');

Config::set('site.authn.ldap_force_protocol', '3');

Config::set('site.authn.ldap_id_attribute', 'samaccountname');

Config::set('loggerhead.url', 'http://vcs.kmfa.net:8080/');

Config::set('issue.default_status', 'new');

Config::set('issue.upload_folder', '/home/sque/workspace/issues-tracker/uploads');

Config::set('mail.default_from', 'issues@dev.kmfa.net');

Config::set('mail.enabled', '0');

?>

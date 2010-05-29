DROP TABLE IF EXISTS `issue_action_details_changes`;
DROP TABLE IF EXISTS `issue_action_tag_changes`;
DROP TABLE IF EXISTS `issue_action_status_changes`;
DROP TABLE IF EXISTS `issue_action_comments`;
DROP TABLE IF EXISTS `issue_actions`;
DROP TABLE IF EXISTS `issue_tags`;
DROP TABLE IF EXISTS `issues`;
DROP TABLE IF EXISTS `issue_statuses`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `memberships`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;

-- Users
CREATE TABLE `users` (
    `username` varchar(50) not null,
    `password` varchar(40) not null,
    `enabled` int(1) not null,
    PRIMARY KEY(`username`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- User profiles
CREATE TABLE `user_profiles` (
    `username` varchar(255) not null,
    `fullname` varchar(255) not null,
    `email` varchar(255) not null,
    PRIMARY KEY(`username`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Memberships
CREATE TABLE `memberships` (
    `username` varchar(255) not null,
    `groupname` varchar(255) not null,
    PRIMARY KEY(`username`, `groupname`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Attachments
CREATE TABLE `attachments` (
    `id` integer auto_increment,
    `filename` varchar(255),
    `filesize` integer,
    `mime` varchar(255),
    `path` varchar(512),
    PRIMARY KEY(`id`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Projects
CREATE TABLE `projects` (
    `name` varchar(255) not null,
    `title` varchar(255) not null,
    `description` TEXT not null,
    `created` DATETIME NOT NULL,
    PRIMARY KEY(`name`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- IssueStatuses
CREATE TABLE `issue_statuses` (
    `name` varchar(50) not null,
    `description` varchar(255) not null,
    PRIMARY KEY(`name`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issues
CREATE TABLE `issues` (
    `id` integer auto_increment not null,
    `title` varchar(255) not null,
    `description` TEXT not null,
    `status` varchar(50) not null,
    `project_name` varchar(255) not null,
    `poster` varchar(255) not null,
    `created` DATETIME not null,
    `assignee` varchar(255),
    `fix_commit` varchar(512),
    PRIMARY KEY(`id`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue Tags
CREATE TABLE `issue_tags` (
    `issue_id` integer not null,
    `tag` varchar(50) not null,
    PRIMARY KEY(`issue_id`, `tag`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue actions
CREATE TABLE `issue_actions` (
    `id` integer auto_increment not null,
    `issue_id` integer not null,
    `type` ENUM('comment', 'status_change', 'tag_change', 'details_change') not null,
    `actor` varchar(255) not null,
    `date` DATETIME not null,
    PRIMARY KEY(`id`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue action comments
CREATE TABLE `issue_action_comments` (
    `id` integer not null,
    `post` TEXT not null,
    `attachment_id` INTEGER,
    PRIMARY KEY(`id`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';DROP TABLE IF EXISTS `issue_action_tag_changes`;

-- Issue action status change
CREATE TABLE `issue_action_status_changes` (
    `id` integer not null,
    `old_status` varchar(50) not null,
    `new_status` varchar(50) not null,
    PRIMARY KEY(`id`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue action tag changes
CREATE TABLE `issue_action_tag_changes` (
    `id` integer not null,
    `operation` ENUM('add', 'remove'),
    `tag` varchar(50) not null,
    PRIMARY KEY(`id`)
)ENGINE = InnoDB
DEFAULT CHARSET = 'UTF8';

-- Issue action edit details
CREATE TABLE `issue_action_details_changes` (
    `id` integer not null,
    `old_title` varchar(255),
    `new_title` varchar(255),
    `old_description` TEXT not null,
    `new_description` TEXT not null,
    PRIMARY KEY(`id`)
)ENGINE = InnoDB
DEFAULT CHARSET = 'UTF8';

INSERT INTO `issue_statuses` (`name`, `description`) values
    ('new', 'This issue has been added but not reviewed.'),
    ('accepted', 'This issue is valid and accepted for fixing.'),
    ('invalid', 'This issue is not valid and has been rejected.'),
    ('fixed', 'This issue was valid and was fixed.');

INSERT INTO `users` (`username`, `password`, `enabled`) values
    ('root', sha1('root'), 1),
    ('sque', sha1('123123'), 1);

INSERT INTO `memberships` (`username`, `groupname`) values
    ('root', 'admin'),
    ('sque', 'dev');
    
INSERT INTO `projects` (`name`, `title`, `description`, `created`) values 
    ('libscan', 'libScan', 'A framework to manage multiple scanners', NOW()),
    ('PolicySphere', 'Policy Sphere', 'A platform to manage security roles', NOW()),
    ('idm', 'IDM', 'Omg kai 3 lol', NOW());
INSERT INTO `issues` (`title`, `description`, `status`, `project_name`) values 
    ('Terastio bug vrethike', 'den to szitiaw bla blalba', 'invalid', 'libscan'),
    ('Ki akki bug vrethike', 'den to szitiaw bla blalba', 'closed', 'libscan'),
    ('Perissotera bug vrethike', 'den to szitiaw bla blalba', 'open', 'libscan'),
    ('Nia nai bug vrethike', 'den to szitiaw bla blalba', 'open', 'PolicySphere'),
    ('ooxi bug vrethike', 'den to szitiaw bla blalba', 'open', 'PolicySphere');

INSERT INTO `issue_actions` (`issue_id`, `type`, `actor`, `date`) values 
    (1, 'comment', 'sque', NOW()),
    (1, 'comment', 'vag', NOW()),
    (1, 'status_change', 'vag', NOW()),
    (1, 'comment', 'sque', NOW()),
    (1, 'tag_change', 'sque', NOW()),
    (2, 'comment', 'vag', NOW()),
    (2, 'status_change', 'vag', NOW()),
    (3, 'comment', 'sque', NOW()),
    (3, 'tag_change', 'sque', NOW()),
    (3, 'tag_change', 'sque', NOW()),
    (3, 'tag_change', 'sque', NOW());

INSERT INTO `issue_tags` (`issue_id`, `tag`) values 
    (1, 'uber'),
    (3, 'dead');
    
INSERT INTO `issue_action_comments` (`id`, `post`) values
    (1, 'Ou uo dou apo pantoy'),
    (2, 'Egw lew pws lew malakies!'),
    (4, 'xaxaxaxax ante re pou elw egw malakeis'),
    (6, 'Egw lew pws lew malakies!'),
    (8, 'Kai egw kai egw lew pws lew malakies!');

INSERT INTO `issue_action_status_changes` (`id`, `old_status`, `new_status`) values
    (3, 'open', 'invalid'),
    (7, 'open', 'closed');

INSERT INTO `issue_action_tag_changes` (`id`, `operation`, `tag`) values
    (5, 'add', 'uber'),
    (9, 'add', 'alive'),
    (10, 'remove', 'alive'),
    (11, 'add', 'dead');

DROP TABLE IF EXISTS `issue_action_details_changes`;
DROP TABLE IF EXISTS `issue_action_status_changes`;
DROP TABLE IF EXISTS `issue_action_comments`;
DROP TABLE IF EXISTS `issue_actions`;
DROP TABLE IF EXISTS `project_tag_count`;
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
    `manager` varchar(255),
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
    PRIMARY KEY(`id`),
    INDEX(`status`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue Tags
CREATE TABLE `issue_tags` (
    `issue_id` integer not null,
    `tag` varchar(50) not null,
    PRIMARY KEY(`issue_id`, `tag`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Project Tags Count
CREATE TABLE `project_tag_count` (
    `project_name` varchar(255) not null,
    `tag` varchar(50) not null,
    `count` integer default 0,
    `percent` float default 0,
    PRIMARY KEY(`project_name`, `tag`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue actions
CREATE TABLE `issue_actions` (
    `id` integer auto_increment not null,
    `issue_id` integer not null,
    `type` ENUM('comment', 'status_change', 'details_change') not null,
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
DEFAULT CHARSET='UTF8';

-- Issue action status change
CREATE TABLE `issue_action_status_changes` (
    `id` integer not null,
    `old_status` varchar(50) not null,
    `new_status` varchar(50) not null,
    PRIMARY KEY(`id`)
)ENGINE=InnoDB
DEFAULT CHARSET='UTF8';

-- Issue action edit details
CREATE TABLE `issue_action_details_changes` (
    `id` integer not null,
    `old_title` varchar(255),
    `new_title` varchar(255),
    `old_description` TEXT not null,
    `new_description` TEXT not null,
    `old_assignee` varchar(255),
    `new_assignee` varchar(255),
    `removed_tags` TEXT DEFAULT '',
    `added_tags` TEXT DEFAULT '',
    PRIMARY KEY(`id`)
)ENGINE = InnoDB
DEFAULT CHARSET = 'UTF8';

INSERT INTO `users` (`username`, `password`, `enabled`) values
    ('root', sha1('root'), 1);

INSERT INTO `memberships` (`username`, `groupname`) values
    ('kpal', 'admin'),
    ('nsteiak', 'admin'),
    ('kpal', 'dev'),
    ('nsteiak', 'dev'),
    ('dlam', 'dev');



/*==============================================================*/
/* DBMS name:      MySQL 5.7+                                   */
/* Created on:     2019/2/27 22:03:11                           */
/*==============================================================*/

SET FOREIGN_KEY_CHECKS = 0;

drop table if exists `xq_system_auth`;

drop table if exists `xq_system_role`;

drop table if exists `xq_system_role_auth`;

drop table if exists `xq_system_user`;

drop table if exists `xq_system_user_role`;

/*==============================================================*/
/* Table: `xq_system_auth`                                      */
/*==============================================================*/
create table `xq_system_auth`
(
    `id`           int(11) unsigned    not null auto_increment comment '权限编号',
    `pid`          int(11) unsigned    not null default 0 comment '上级权限',
    `app`          varchar(191)        not null default '' comment '所属应用',
    `name`         varchar(191)        not null default '' comment '权限标识',
    `title`        varchar(191)        not null default '' comment '权限标题',
    `icon`         varchar(191)        not null default '' comment '权限图标',
    `rule`         varchar(191)        not null default '' comment '权限规则',
    `extend`       text comment '扩展信息',
    `menu`         tinyint(1) unsigned not null default 0 comment '是否为菜单 0=>否,1=>是',
    `debug`        tinyint(1) unsigned not null default 0 comment '是否仅调试模式启用 0=>否,1=>是',
    `status`       tinyint(1) unsigned not null default 0 comment '是否启用 0=>禁用,1=>启用',
    `sort`         int(11)             not null default 0 comment '排序值',
    `lock_version` int(11) unsigned    not null default 0 comment '乐观锁',
    `create_time`  int(10) unsigned    not null default 0 comment '创建时间',
    `update_time`  int(10) unsigned    not null default 0 comment '更新时间',
    `delete_time`  int(10) unsigned    not null default 0 comment '删除时间',
    primary key (`id`),
    key `ak_system_auth_menu` (`menu`),
    key `ak_system_auth_debug` (`debug`),
    key `ak_system_auth_status` (`status`),
    key `ak_system_auth_sort` (`sort`)
)
    engine = innodb
    default charset = utf8mb4
    collate = utf8mb4_unicode_ci;

alter table `xq_system_auth`
    comment '权限表';

/*==============================================================*/
/* Table: `xq_system_role`                                      */
/*==============================================================*/
create table `xq_system_role`
(
    `id`           int(11) unsigned    not null auto_increment comment '角色编号',
    `pid`          int(11) unsigned    not null default 0 comment '上级角色',
    `name`         varchar(191)        not null default '' comment '角色标识',
    `title`        varchar(191)        not null default '' comment '角色标题',
    `status`       tinyint(1) unsigned not null default 0 comment '是否启用 0=>禁用,1=>启用',
    `sort`         int(11)             not null default 0 comment '排序值',
    `lock_version` int(11) unsigned    not null default 0 comment '乐观锁',
    `create_time`  int(10) unsigned    not null default 0 comment '创建时间',
    `update_time`  int(10) unsigned    not null default 0 comment '更新时间',
    `delete_time`  int(10) unsigned    not null default 0 comment '删除时间',
    primary key (`id`),
    key `ak_system_role_status` (`status`),
    key `ak_system_role_sort` (`sort`)
)
    engine = innodb
    default charset = utf8mb4
    collate = utf8mb4_unicode_ci;

alter table `xq_system_role`
    comment '角色表';

/*==============================================================*/
/* Table: `xq_system_role_auth`                                 */
/*==============================================================*/
create table `xq_system_role_auth`
(
    `rid` int(11) unsigned not null default 0 comment '角色ID',
    `aid` int(11) unsigned not null default 0 comment '权限ID',
    key `ak_system_role_auth_rid` (`rid`),
    key `ak_system_role_auth_aid` (`aid`),
    unique key `ak_system_role_auth_rid_aid` (`rid`, `aid`),
    constraint `fk_system_role_auth_rid` foreign key (`rid`)
        references `xq_system_role` (`id`) on delete restrict on update restrict,
    constraint `fk_system_role_auth_aid` foreign key (`aid`)
        references `xq_system_auth` (`id`) on delete restrict on update restrict
)
    engine = innodb
    default charset = utf8mb4
    collate = utf8mb4_unicode_ci;

alter table `xq_system_role_auth`
    comment '角色权限对应表';

/*==============================================================*/
/* Table: `xq_system_user`                                      */
/*==============================================================*/
create table `xq_system_user`
(
    `id`                       int(11) unsigned not null auto_increment comment '用户编号',
    `username`                 varchar(191)     not null default '' comment '账号',
    `password`                 varchar(191)     not null default '' comment '密码',
    `salt`                     varchar(191)     not null default '' comment '密码加/解密 密钥',
    `access_token`             varchar(191)              default '' comment 'access_token',
    `access_token_create_time` int(10) unsigned          default 0 comment 'access_token_create_time',
    `access_token_expires_in`  int(10) unsigned          default 0 comment 'access_token_expires_in',
    `status`                   tinyint(1)       not null default 0 comment '是否启用 -1=>待审核,0=>禁用,1=>启用',
    `sort`                     int(11)          not null default 0 comment '排序值',
    `lock_version`             int(11) unsigned not null default 0 comment '乐观锁',
    `create_time`              int(10) unsigned not null default 0 comment '创建时间',
    `update_time`              int(10) unsigned not null default 0 comment '更新时间',
    `delete_time`              int(10) unsigned not null default 0 comment '删除时间',
    primary key (`id`),
    unique key `ak_system_user_access_token` (`access_token`),
    unique key `ak_system_user_username` (`username`),
    key `ak_system_user_sort` (`sort`),
    key `ak_system_user_status` (`status`)
)
    engine = innodb
    default charset = utf8mb4
    collate = utf8mb4_unicode_ci;

alter table `xq_system_user`
    comment '用户表';

/*==============================================================*/
/* Table: `xq_system_user_role`                                 */
/*==============================================================*/
create table `xq_system_user_role`
(
    `uid` int(11) unsigned not null default 0 comment '用户ID',
    `rid` int(11) unsigned not null default 0 comment '角色ID',
    key `ak_system_user_role_uid` (`uid`),
    key `ak_system_user_role_rid` (`rid`),
    unique key `ak_system_user_role_uid_rid` (`uid`, `rid`),
    constraint `fk_system_user_role_rid` foreign key (`rid`)
        references `xq_system_role` (`id`) on delete restrict on update restrict,
    constraint `fk_system_user_role_uid` foreign key (`uid`)
        references `xq_system_user` (`id`) on delete restrict on update restrict
)
    engine = innodb
    default charset = utf8mb4
    collate = utf8mb4_unicode_ci;

alter table `xq_system_user_role`
    comment '用户角色对应表';

SET FOREIGN_KEY_CHECKS = 1;

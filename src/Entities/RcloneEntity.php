<?php


namespace trongloikt192\Utils\Entities;


class RcloneEntity extends BaseEntity
{
    /**
     * @var string
     */
    public $group;

    /**
     * @var string
     */
    public $rclone_name;

    /**
     * @var string
     */
    public $rclone_type;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $client_id;

    /**
     * @var string
     */
    public $client_secret;

    /**
     * @var string
     */
    public $token;

    /**
     * @var string
     */
    public $token_expire_at;

    /**
     * @var string
     */
    public $gdrive_scope;

    /**
     * @var string
     */
    public $gdrive_team;

    /**
     * @var string
     */
    public $onedrive_id;

    /**
     * @var string
     */
    public $onedrive_type;

    /**
     * @var string
     */
    public $onedrive_tenant_id;

    /**
     * @var string
     */
    public $webdav_url;

    /**
     * @var string
     */
    public $webdav_vendor;
}

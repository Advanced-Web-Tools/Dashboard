<?php

namespace Dashboard\cli;

use admin\Admin;
use cli\interfaces\CLICommand;

class AdminCreationCommand implements CLICommand
{

    private string $result;
    public function getCommand(): string
    {
        return "admin_create";
    }

    public function getHelp(): string
    {
        return "Creates a new admin user. Format 'admin-create username password email firstname lastname permission(0-2)'";
    }

    public function getArguments(): array
    {
        return [
            "admin_create" => "Creates a new admin user. Format 'admin-create username password email firstname lastname permission(0-2)'"
        ];
    }

    public function execute(string $command, array $args = []): void
    {
        $admin = new Admin();
        $admin->setUsername($args[0]);
        $admin->setPassword($args[1]);
        $admin->setEmail($args[2]);
        $admin->setFirstname($args[3]);
        $admin->setLastname($args[4]);
        $admin->setPermLevel($args[5]);
        $admin->createToken();
        $res = $admin->register();
        if ($res) {
            $this->result = "Admin user created successfully";
        } else {
            $this->result = "Admin user creation failed";
        }
    }

    public function result(): string
    {
        return $this->result;
    }
}
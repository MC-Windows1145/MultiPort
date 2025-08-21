<?php
/*
 *
 *  _____           _    __________________ _______  _______  _______ _________
 *  (       )|\     /|( \   \__   __/\__   __/(  ____ )(  ___  )(  ____ )\__   __/
 *  | () () || )   ( || (      ) (      ) (   | (    )|| (   ) || (    )|   ) (   
 *  | || || || |   | || |      | |      | |   | (____)|| |   | || (____)|   | |   
 *  | |(_)| || |   | || |      | |      | |   |  _____)| |   | ||     __)   | |   
 *  | |   | || |   | || |      | |      | |   | (      | |   | || (\ (      | |   
 *  | )   ( || (___) || (____/\| |   ___) (___| )      | (___) || ) \ \__   | |   
 *  |/     \|(_______)(_______/)_(   \_______/|/       (_______)|/   \__/   )_(  
 *  
 *   Copyright (c) 2025 Windows1145
 *
 * - 基于 MIT 协议授权：
 * - 允许自由使用、复制、修改、合并、发布、分发
 * - 唯一要求：保留此版权声明和协议文本
 * 
 *  @link https://github.com/MC-Windows1145/MultiPort
 */
namespace MultiPort;

use pocketmine\network\RakLibInterface;
use pocketmine\Server;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;

class CustomBoundRakLibInterface extends RakLibInterface
{
    protected $customName = "";
    public function __construct(Server $server, $ip = null, $port = null/*, $internalPort = null*/)
    {
        $reflection = new \ReflectionClass(parent::class);

        $serverProperty = $reflection->getProperty('server');
        $serverProperty->setAccessible(true);
        $serverProperty->setValue($this, $server);

        $identifiersProperty = $reflection->getProperty('identifiers');
        $identifiersProperty->setAccessible(true);
        $identifiersProperty->setValue($this, []);

        $bindIp = ($ip !== null) ? $ip : $server->getIp();
        $bindPort = ($port !== null) ? $port : $server->getPort();
        //$internalPort = ($internalPort !== null) ? $internalPort : $bindPort;

        if ($bindIp === "") {
            $bindIp = "0.0.0.0";
        }

        $rakLib = new RakLibServer($server->getLogger(), $server->getLoader(), $bindPort, $bindIp);

        $rakLibProperty = $reflection->getProperty('rakLib');
        $rakLibProperty->setAccessible(true);
        $rakLibProperty->setValue($this, $rakLib);

        $interface = new ServerHandler($rakLib, $this);

        $interfaceProperty = $reflection->getProperty('interface');
        $interfaceProperty->setAccessible(true);
        $interfaceProperty->setValue($this, $interface);
    }

    /**
     * 反射获取ServerHandler
     * @return null|ServerHandler
     */
    protected function getHandler() {
        $reflection = new \ReflectionClass(parent::class);
        $interfaceProperty = $reflection->getProperty('interface');
        $interfaceProperty->setAccessible(true);
        return $interfaceProperty->getValue($this);
    }

    public function setCustomName($name)
    {
        $this->customName = $name;
        $this->getHandler()->sendOption("name", $name);
    }

    public function setName($name)
    {
        if (empty($this->customName)) {
            parent::setName($name);
        }
    }
}

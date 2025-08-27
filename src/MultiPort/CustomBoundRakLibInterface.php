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

use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\RakLibInterface;
use pocketmine\Server;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;

class CustomBoundRakLibInterface extends RakLibInterface
{
    /**
     * @var array<string, string>
     * @throws \ReflectionException
     */
    protected $customName = [];
    public function __construct(Server $server, $ip = null, $port = null/*, $internalPort = null*/, $customName = [])
    {
        $this->setProperty("server", $server);
        $this->setProperty("identifiers", []);

        $bindIp = $ip ?? $server->getIp();
        $bindPort = $port ?? $server->getPort();
        //$internalPort = ($internalPort !== null) ? $internalPort : $bindPort;

        if ($bindIp === "") {
            $bindIp = "0.0.0.0";
        }

        $rakLib = new RakLibServer($server->getLogger(), $server->getLoader(), $bindPort, $bindIp);
        $this->setProperty("rakLib", $rakLib);

        $interface = new ServerHandler($rakLib, $this);
        $this->setProperty("interface", $interface);

        $this->customName = $customName;
    }

    /**
     * 用反射设置属性值
     * @throws \ReflectionException
     */
    protected function setProperty(string $name, $value)
    {
        $reflection = new \ReflectionClass(parent::class);
        $serverProperty = $reflection->getProperty($name);
        $serverProperty->setAccessible(true);
        $serverProperty->setValue($this, $value);
    }

    /**
     * 用反射获取属性值
     * @return null|object
     * @throws \ReflectionException
     */
    protected function getProperty(string $name)
    {
        $reflection = new \ReflectionClass(parent::class);
        $interfaceProperty = $reflection->getProperty($name);
        $interfaceProperty->setAccessible(true);
        return $interfaceProperty->getValue($this);
    }

    protected function getHandler()
    {
        return $this->getProperty("interface");
    }

    protected function getServer()
    {
        return $this->getProperty("server");
    }

    public function setName($name)
    {
        if (empty($this->customName)) {
            parent::setName($name);
        } else {
            $this->updateCustomName(); // 有事才会来找我
        }
    }

    public function updateCustomName()
    {
        $this->sendFullName($this->buildCustomName());
    }

    protected function buildCustomName()
    {
        $info = $this->getServer()->getQueryInformation();
        $customName = [
            "edition" => "MCPE",
            "motd" => $info->getServerName(),
            "protocol" => ProtocolInfo::CURRENT_PROTOCOL,
            "version" => \pocketmine\MINECRAFT_VERSION_NETWORK,
            "onlineplayers" => $info->getPlayerCount(),
            "maxplayers" => $info->getMaxPlayerCount()
        ];

        $customName = array_merge($customName, $this->customName);

        $customName = implode(";", [
            $customName["edition"],
            addcslashes($customName["motd"], ";"),
            $customName["protocol"],
            $customName["version"],
            $customName["onlineplayers"],
            $customName["maxplayers"]
        ]);

        return $customName;
    }

    protected function sendFullName($fullName)
    {
        $this->getHandler()->sendOption("name", $fullName);
    }
}

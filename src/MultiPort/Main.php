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

use pocketmine\plugin\PluginBase;
use pocketmine\network\RakLibInterface;
use pocketmine\Server;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use ReflectionClass;

class Main extends PluginBase
{
    /** @var RakLibInterface[] */
    private $extraInterfaces = [];

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->getLogger()->info("§e尝试读取配置文件");
        $this->saveDefaultConfig();

        $this->registerExtraPorts();
        $this->getLogger()->info("§a多端口插件已启用!");
    }

    private function registerExtraPorts()
    {
        $ports = $this->getConfig()->get("ports", []);
        $ip = $this->getServer()->getIp();
        $mainPort = $this->getServer()->getPort();

        if (empty($ports)) {
            $this->getLogger()->info("§e未配置额外端口");
            return;
        }

        $successCount = 0;
        foreach ($ports as $port) {
            $port = (int) $port;
            if ($port >= 1 && $port <= 65535 && $port !== $mainPort) {
                if ($this->registerNewInterface($ip, $port)) {
                    $successCount++;
                }
            } else {
                $this->getLogger()->warning("端口 {$port} 跳过: 端口无效或与主端口 {$mainPort} 冲突");
            }
        }

        if ($successCount > 0) {
            $this->getLogger()->info("§a成功注册 {$successCount} 个额外端口");
        }
    }

    private function registerNewInterface($ip, $port)
    {
        try {
            $server = $this->getServer();
            $network = $server->getNetwork();

            $interface = new MultiPortRakLibInterface($server, $ip, $port);
            $network->registerInterface($interface);

            $this->extraInterfaces[] = $interface;

            $this->getLogger()->info("§a成功注册端口: " . $port);
            return true;

        } catch (\Exception $e) {
            $this->getLogger()->error("端口 {$port} 注册失败: " . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            $this->getLogger()->error("端口 {$port} 监听失败(严重错误): " . $e->getMessage());
            return false;
        }
    }

    public function onDisable()
    {
        foreach ($this->extraInterfaces as $interface) {
            try {
                $interface->shutdown();
                $interface->emergencyShutdown();
            } catch (\Exception $e) {
                // 忽略关闭时的错误
            }
        }
        $this->extraInterfaces = [];

        $this->getLogger()->info("§c多端口插件已禁用!");
    }
}
class MultiPortRakLibInterface extends RakLibInterface
{
    public function __construct(Server $server, $ip = null, $port = null, $internalPort = null)
    {
        $reflection = new ReflectionClass(parent::class);

        $serverProperty = $reflection->getProperty('server');
        $serverProperty->setAccessible(true);
        $serverProperty->setValue($this, $server);

        $identifiersProperty = $reflection->getProperty('identifiers');
        $identifiersProperty->setAccessible(true);
        $identifiersProperty->setValue($this, []);

        $bindIp = ($ip !== null) ? $ip : $server->getIp();
        $bindPort = ($port !== null) ? $port : $server->getPort();
        $internalPort = ($internalPort !== null) ? $internalPort : $bindPort;

        if ($bindIp === "") {
            $bindIp = "0.0.0.0";
        }

        $rakLib = new RakLibServer($server->getLogger(), $server->getLoader(), $internalPort, $bindIp);

        $rakLibProperty = $reflection->getProperty('rakLib');
        $rakLibProperty->setAccessible(true);
        $rakLibProperty->setValue($this, $rakLib);

        $interface = new ServerHandler($rakLib, $this);

        $interfaceProperty = $reflection->getProperty('interface');
        $interfaceProperty->setAccessible(true);
        $interfaceProperty->setValue($this, $interface);
    }
}

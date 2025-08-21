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
use pocketmine\plugin\PluginBase;
use pocketmine\network\RakLibInterface;

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
        $bindings = $this->getConfig()->get("binding", []);
        $mainIp = $this->getServer()->getIp();
        $mainPort = $this->getServer()->getPort();

        if (empty($bindings)) {
            $this->getLogger()->info("§e未配置额外绑定");
            return;
        }

        foreach ($bindings as $bindingConfig) {
            $ip = $mainIp;
            $port = $mainPort;

            $customName = "";

            if (is_array($bindingConfig)) {
                if (isset($bindingConfig["port"])) {
                    $port = $bindingConfig["port"];
                }

                if (isset($bindingConfig["ip"])) {
                    $ip = $bindingConfig["ip"];
                }

                if (isset($bindingConfig["name"])) {
                    $info = $this->getServer()->getQueryInformation();
                    $customName = [
                        "edition" => "MCPE",
                        "motd" => $info->getServerName(),
                        "protocol" => ProtocolInfo::CURRENT_PROTOCOL,
                        "version" => \pocketmine\MINECRAFT_VERSION_NETWORK,
                        "onlineplayers" => $info->getPlayerCount(),
                        "maxplayers" => $info->getMaxPlayerCount()
                    ];

                    if (is_array($bindingConfig["name"])) {
                        $customName = array_merge($customName, $bindingConfig["name"]);
                    } else {
                        $customName["motd"] = (string) $bindingConfig["name"];
                    }

                    $customName = implode(";", [
                        $customName["edition"],
                        addcslashes($customName["motd"], ";"),
                        $customName["protocol"],
                        $customName["version"],
                        $customName["onlineplayers"],
                        $customName["maxplayers"]
                    ]);
                }
            } else {
                $port = (int) $bindingConfig;
            }

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $this->getLogger()->error("IPV4地址 $ip 不合法");
                continue;
            }

            if ($port < 1 || $port > 65535) {
                $this->getLogger()->error("端口 $port 无效");
                continue;
            }

            if ($port === $mainPort && $ip === $mainIp) {
                $this->getLogger()->error("跳过 $ip:$port : 已被主监听地址占用");
                continue;
            }

            if (isset($this->extraInterfaces["$ip:$port"])) {
                $this->getLogger()->error("$ip:$port 已存在");
                continue;
            }

            if ($this->registerNewInterface($ip, $port, $customName) !== null) { // TODO: 支持新版格式 服务器ID、服务器软件名称、游戏模式，屏蔽Query
                $this->getLogger()->info("§a成功注册自定义RakLibInterface: $ip:$port");
            }
        }

        $this->getLogger()->info("§a注册了 " . count($this->extraInterfaces) . " 个自定义RakLibInterface");
    }

    /**
     * 构造CustomBoundRakLibInterface并注册到Network，失败返回null
     * @param string $ip
     * @param int $port
     * @param string $customName
     * @return null|CustomBoundRakLibInterface
     */
    private function registerNewInterface($ip, $port, $customName = "")
    {
        try {
            $server = $this->getServer();
            $network = $server->getNetwork();

            $interface = new CustomBoundRakLibInterface($server, $ip, $port);
            if (!empty($customName)) {
                $interface->setCustomName($customName);
            }
            $network->registerInterface($interface);

            $this->extraInterfaces["$ip:$port"] = $interface;
            return $interface;
        } catch (\Exception $e) {
            $this->getLogger()->error("端口 $port 注册失败:");
            $this->getLogger()->logException($e);
        } catch (\Throwable $e) {
            $this->getLogger()->error("端口 $port 监听失败(严重错误):");
            $this->getLogger()->logException($e);
        }
        return null;
    }

    public function onDisable()
    {
        foreach ($this->extraInterfaces as $interface) {
            try {
                $interface->shutdown();
                $this->getServer()->getNetwork()->unregisterInterface($interface);
            } catch (\Exception $e) {
                $this->getLogger()->logException($e);
            }
        }
        $this->extraInterfaces = [];

        $this->getLogger()->info("§c多端口插件已禁用!");
    }
}
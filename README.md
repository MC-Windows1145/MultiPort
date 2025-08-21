# MultiPort - PMMP2.0.0多端口插件

一个用于0.14.X的PocketMine-MP或Genisys服务器的普通多端口插件。

## 功能特性
- [x] 可用多个端口加入游戏
- [x] 无需另外修改RakLibInterface
- [x] 配置简单
- [x] 独立MOTD配置

## TODO: 
- [ ] IPV6

## 安装方法
1. 下载 `MultiPort-main.zip` 文件
2. 解压并放入服务器 `plugins` 文件夹
3. 重启服务器生成配置
4. 编辑 `plugins/MultiPort/config.yml`
5. 再次重启服务器

## 配置示例
```yaml
# config.yml
binding:
- 19131 #端口1
- 19132 #端口2
- port: 19133
  ip: 192.168.1.100
- port: 19134
  name: Example
- port: 19134
  name:
    motd: Example
    protocol: 46
    version: 0.14.1
    onlineplayers: 999
    maxplayers: 1234
```
~~因为懒，所以有10%是AI???~~

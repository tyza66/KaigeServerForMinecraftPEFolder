<?php
 
/*
__PocketMine Plugin__
name=TrustPlayer
description=You can't place or break anithing if the admin don't accept you on the server.
version=1.5
apiversion=10,11
author=InusualZ
class=TrustPlayer
*/
 
class TrustPlayer implements Plugin{
    private $api, $config;
 
    public function __construct(ServerAPI $api, $server = false){
        $this->api  = $api;
    }
     
    public function init(){                    
        $this->api->addHandler('player.block.touch', array($this, 'handler'), 15);
        $this->api->addHandler('player.block.activate', array($this, 'handler'), 15);
        $this->api->addHandler('player.block.pickup', array($this, 'handler'), 15);
        $this->api->addHandler('player.interact', array($this, 'handler'));
        $this->api->addHandler('player.quit', array($this, 'handler'), 15);
        $this->api->addHandler('entity.explosion', array($this, 'handler'), 15);

        $this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
                "plugin-version"    => 1.5,
                "user-list"         => array(),
                "send-msg"          => true,
                "detrust-on-quit"   => false,
                "msg-on-trust"      => "[TrustPlayer] You have been trusted.",
                "msg-on-detrust"    => "[TrustPlayer] You have been detrusted",
                "msg-break"         => "[TrustPlayer] You can't break block.",
                "msg-place"         => "[TrustPlayer] You can't place block.",
                "msg-pickup"        => "[TrustPlayer] You can't pickup anything.",
                "msg-activate"      => "[TrustPlayer] You can't activate a block.",
                "msg-explosion"     => "[TrustPlayer] You can't ignite nothing",
                "msg-interact"      => "[TrustPlayer] You can not interact with other people"
         ));
                
        if($this->config->get('plugin-version') != 1.5)
        {
            unlink($this->api->plugin->configPath($this) . "config.yml");
            $this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
            "plugin-version"    => 1.5,
            "user-list"         => array(),
            "send-msg"          => true,
            "detrust-on-quit"   => false,
            "msg-on-trust"      => "[TrustPlayer] You have been trusted.",
            "msg-on-detrust"    => "[TrustPlayer] You have been detrusted",
            "msg-break"         => "[TrustPlayer] You can't break block.",
            "msg-place"         => "[TrustPlayer] You can't place block.",
            "msg-pickup"        => "[TrustPlayer] You can't pickup anything.",
            "msg-activate"      => "[TrustPlayer] You can't activate a block.",
            "msg-explosion"     => "[TrustPlayer] You can't ignite nothing",
            "msg-interact"      => "[TrustPlayer] You can not interact with other people"
         ));
        }
        $this->api->console->register('trust', '[TrustPlayer] Trust a player.', array($this, 'command'));
        $this->api->console->register('detrust', '[TrustPlayer] Destrust a player.', array($this, 'command'));
    }
 
    public function __destruct()
    {
    }
 
    public function handler(&$data, $event) 
    {
        switch ($event) 
        {
            case "player.block.touch":
                if (!$this->usernameExist($data['player']->username))
                {
                    if ($this->config->get('send-msg') == true)
                    {
                        switch ($data["type"]) 
                        {
                            
                            case "place":
                                $data["player"]->eventHandler($this->config->get('msg-place'), "server.chat");
                                 break;
                            case "break":
                                $data["player"]->eventHandler($this->config->get('msg-break'), "server.chat");
                                break;
                            }
                    }                        
                    return false;
                }
                return true;
            break;

            case "entity.explosion":
                if (!$this->usernameExist($data['player']->username))
                {                
                    return false;
                }
                return true;
            break;

            case "player.block.activate":
                if (!$this->usernameExist($data['player']->username))
                {
                    if($this->config->get('send-msg') == true)
                    {
                        $data["player"]->eventHandler($this->config->get('msg-activate'), "server.chat");
                    }                
                    return false;
                }
                return true;
            break;

            case 'player.block.pickup':
                if (!$this->usernameExist($data['player']->username))
                {
                    if($this->config->get('send-msg') == true)
                    {
                        $data["player"]->eventHandler($this->config->get('msg-pickup'), "server.chat");
                    }                
                    return false;
                }
                return true;
            break;

            case 'player.quit':
                if($this->config->get('detrust-on-quit') === true)
                {
                    if($this->usernameExist($data->username))
                    {
                           $this->detrust($data->username);
                    }
                }
            break;

            case 'player.interact':
                if(!$this->usernameExist($data['entity']->player->username))
                {
                    if($this->config->get('send-msg') == true)
                    {
                        $data['entity']->player->sendChat($this->config->get('msg-interact'));
                    }
                }
                break;
        }
    }        
 
    public function command($cmd, $params)
    {
        switch ($cmd)
        {
            case "trust":
                $username = array_shift($params);
                if(empty($username) || $username == NULL)
                {
                    console('[INFO] Usage: /trust <player>');
                }
                else
                {
                    if($this->api->player->get($username) instanceof Player)
                    {
                        if($this->usernameExist($username))
                        {
                            console('[TrustPlayer] Username already exist.');
                        }
                        else
                        {
                            $this->api->player->get($username)->eventHandler($this->config->get('msg-on-trust'), "server.chat");
                            $this->trust($username);
                        }
                    }
                    else
                    {
                        console("[TrustPlayer] The player {$username} don't exist or not is connected on the server.");
                    }
                }
            break;
            
            case "detrust":
                $username = array_shift($params);
                if(empty($username) || $username == NULL)
                {
                    console('[INFO] Usage: /detrust <player>');
                }
                else
                {
                    if($this->usernameExist($username))
                    {
                        $this->api->player->get($username)->eventHandler($this->config->get('msg-on-detrust'), "server.chat");
                        $this->detrust($username);
                        console('[TrustPlayer] The username has been deleted.');
                    }
                    else
                    {
                        console("[TrustPlayer] Username don't exist");
                    }
                }
            break;
        }
    }
    public function trust($username) 
    {
        if (!in_array($username, $this->config->get("user-list"))) 
        {
            $c = $this->config->get("user-list");
            $c[] = $username;
            $this->config->set("user-list", $c);
            $this->config->save();
            console('[TrustPlayer] The player has been trusted.');
            return;
        } 
        console('[TrustPlayer]The username alredy exist on the list.');

    }

    public function detrust($username) 
    {
        $c = $this->config->get("user-list");
        $key = array_search($username, $c);
        unset($c[$key]);
        $this->config->set("user-list", $c);
        $this->config->save();
    }

    public function usernameExist($name)
    {
        return in_array($name, $this->config->get("user-list"));
    }
}
?>
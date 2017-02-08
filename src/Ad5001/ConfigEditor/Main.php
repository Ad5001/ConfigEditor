<?php
#   _____                __  _         ______     _  _  _               
#  / ____|              / _|(_)       |  ____|   | |(_)| |              
# | |      ___   _ __  | |_  _   __ _ | |__    __| | _ | |_  ___   _ __ 
# | |     / _ \ | '_ \ |  _|| | / _` ||  __|  / _` || || __|/ _ \ | '__|
# | |____| (_) || | | || |  | || (_| || |____| (_| || || |_| (_) || |   
#  \_____|\___/ |_| |_||_|  |_| \__, ||______|\__,_||_| \__|\___/ |_|   
#                                __/ |                                  
#                               |___/                                   
# Too lazy to go to a folder and edit configs? Do it directly in game !


namespace Ad5001\ConfigEditor;

use pocketmine\command\CommandSender;

use pocketmine\command\Command;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

use pocketmine\Server;

use pocketmine\utils\Config;

use pocketmine\Player;

use pocketmine\event\TranslationContainer;






class Main extends PluginBase{

    const PREFIX = "§o§l§7[§r§l§bConfig§3Editor§o§7] §r§f";

    protected $cfgs = [];

    public function onEnable(){
        $this->saveDefaultConfig();
    }


    /*
    Called when one of the defined commands of the plugin has been called
    @param     $sender     \pocketmine\command\CommandSender
    @param     $cmd          \pocketmine\command\Command
    @param     $label         mixed
    @param     $args          array
    return bool
    */
    public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $args): bool {
         switch($cmd->getName()) {

                /*
                Edits the config opened by a player.
                Usage: /cfg <read|set|get|del|save> [key] [value]
                */
                case "cfg":
                $overwritePermission = $sender->hasPermission("cfg.cmd.edit") || $sender->hasPermission("cfg.cmd");
                if(!isset($this->cfgs[$sender->getName()])) {
                    $sender->sendMessage(self::PREFIX . "§cNo opened config. Open one using /opencfg <plugin name> <config file>.");
                    return true;
                }
                if(isset($args[0])) {
                    $cfg = $this->cfgs[$sender->getName()][0];
                    $file = $this->cfgs[$sender->getName()][1];
                    switch($args[0]) {
                        /*
                        Reads the content of the config (does not apply changes of unsaved data)
                        Usage: /cfg read
                        */
                        case "read":
                        $perm = "cfg.cmd.edit.read";
                        if($overwritePermission || $sender->hasPermission($perm)) {
                            $sender->sendMessage(self::PREFIX . "§aContents of config: " . file_get_contents($file));
                        } else {
                            $this->sendPermissionMessage($sender);
                        }
                        break;
                        /*
                        Sets a data in the config
                        Usage: /cfg set <key> <value...>
                        */
                        case "set":
                        $perm = "cfg.cmd.edit.set";
                        if($overwritePermission || $sender->hasPermission($perm)) {
                            if(isset($args[2])) {
                                $oldValue = "n't set";
                                if($cfg->exists($args[1])) $oldValue = " " . $cfg->get($args[1]);
                                $name = $args[1];
                                unset($args[0], $args[1]);
                                $cfg->set($name, implode(" ", $args));
                                $sender->sendMessage(self::PREFIX . "§aSuccefully set value '$name' (was$oldValue) to " .implode(" ", $args) . ".");
                            } else {
                                $sender->sendMessage(new TranslationContainer("commands.generic.usage", ["/cfg set <key> <value...>"]));
                            }
                        } else {
                            $this->sendPermissionMessage($sender);
                        }
                        break;
                        /*
                        Gets a data in the config
                        Usage: /cfg get <key>
                        */
                        case "get":
                        $perm = "cfg.cmd.edit.get";
                        if($overwritePermission || $sender->hasPermission($perm)) {
                            if(isset($args[1])) {
                                if(!$cfg->exists($args[1])) {
                                    $sender->sendMessage(self::PREFIX . "§cKey \"{$args[1]}\" does not exists in your current opened config.");
                                    return true;
                                }
                            $sender->sendMessage(self::PREFIX . "§a\"{$args[1]}\" in your config is " . $cfg->get($args[1]));
                            } else {
                                $sender->sendMessage(new TranslationContainer("commands.generic.usage", ["/cfg get <key> "]));
                            }
                        } else {
                            $this->sendPermissionMessage($sender);
                        }
                        break;
                        /*
                        Deletes a data from the config.
                        Usage: /cfg del <key>
                        */
                        case "del":
                        $perm = "cfg.cmd.edit.del";
                        if($overwritePermission || $sender->hasPermission($perm)) {
                            if(isset($args[1])) {
                                $oldValue = "n't set";
                                if($cfg->exists($args[1])) $oldValue = " " . $cfg->get($args[1]);
                                $cfg->remove($args[1]);
                                $sender->sendMessage(self::PREFIX . "§aSuccefully removed value '$args[1]' (was$oldValue).");
                            } else {
                                $sender->sendMessage(new TranslationContainer("commands.generic.usage", ["/cfg del <key>"]));
                            }
                        } else {
                            $this->sendPermissionMessage($sender);
                        }
                        break;
                        /*
                        Saves the config and reloads it on the plugin using it if possible.
                        Usage: /cfg save
                        */
                        case "save":
                        $perm = "cfg.cmd.edit.save";
                        if($overwritePermission || $sender->hasPermission($perm)) {
                            $cfg->save();
                            $sender->sendMessage(self::PREFIX . "§aSuccefully saved config !");
                            $this->getLogger()->debug(pathinfo($file, PATHINFO_FILENAME) . " -> " . pathinfo(pathinfo($file, PATHINFO_DIRNAME), PATHINFO_FILENAME));
                            if(pathinfo($file, PATHINFO_FILENAME) == "config") {
                                if($this->getServer()->getPluginManager()->getPlugin(pathinfo(pathinfo($file, PATHINFO_DIRNAME), PATHINFO_FILENAME)) !== null) {
                                    $this->getServer()->getPluginManager()->getPlugin(pathinfo(pathinfo($file, PATHINFO_DIRNAME), PATHINFO_FILENAME))->reloadConfig();
                                    $sender->sendMessage(self::PREFIX . "§aSuccefully reloaded config on plugin " . pathinfo(pathinfo($file, PATHINFO_DIRNAME), PATHINFO_FILENAME) . "!");
                                }
                            }
                        } else {
                            $this->sendPermissionMessage($sender);
                        }
                        break;
                    }
                }
                return true;
                break;

                /*
                Opens a config for a player.
                Usage: /opencfg <plugin> <file>
                */
                case "opencfg":
                if(isset($args[1])) {
                    if(file_exists($this->getServer()->getPluginPath() . $args[0] . "/" . $args[1])) {
                        if(in_array($args[1], $this->getConfigs($this->getServer()->getPluginPath() . $args[0]))) {
                            if(!in_array($args[0] . "/" . $args[1], $this->getConfig()->get("Forbidden configs"))) {
                                $this->cfgs[$sender->getName()] = [new Config($this->getServer()->getPluginPath() . $args[0] . "/" . $args[1]), $this->getServer()->getPluginPath() . $args[0] . "/" . $args[1]];
                                $sender->sendMessage(self::PREFIX . "§aSuccefully opened config {$args[1]} of plugin {$args[0]}.");
                            } else {
                                $sender->sendMessage(self::PREFIX . "§cYou aren't allowed to open this config.");
                            }
                        } else {
                            $sender->sendMessage(self::PREFIX . "§c{$args[1]} is not a valid config !");
                        }
                    } else {
                        $sender->sendMessage(self::PREFIX . "§cNo config found with name {$args[1]} in datafolder of plugin {$args[0]}.");
                    }
                } elseif(isset($args[0])) {
                    if(is_dir($this->getServer()->getPluginPath() . $args[0])) {
                        $sender->sendMessage(self::PREFIX . "§aConfigs in plugin {$args[0]}'s datafolder: " . implode(", ", $this->getConfigs($this->getServer()->getPluginPath() . $args[0])) . ".");
                        $sender->sendMessage(self::PREFIX . "§aOpen one using /opencfg {$args[0]} <file of the config>.");
                    } else {
                        $sender->sendMessage(self::PREFIX . "§cPlugin with name {$args[0]} does not exists or doens't have a datafolder.");
                    }
                } else {
                    return false;
                }
                return true;
                break;

                /*
                Closes player's a config.
                Usage: /closecfg
                */
                case "closecfg":
                if(isset($this->cfgs[$sender->getName()])) {
                    unset($this->cfgs[$sender->getName()]);
                    $sender->sendMessage(self::PREFIX . "§aSuccefully closed your current opened config.");
                } else {
                    $sender->sendMessage(self::PREFIX . "§cYou doesn't have any opened config.");
                }
                return true;
                break;
         }
    }


/*        _____  _____ 
    /\    |  __ \|_   _|
   /  \   | |__) | | |  
  / /\ \  |  ___/  | |  
 / ____ \ | |     _| |_ 
/_/    \_\|_|    |_____|
*/



    /*
    Returns all configs in a folder (does not return other files).
    @param     $folder    string
    @return array
    */
    public function getConfigs(string $folder) : array {
        if(is_dir($folder)) {
            $configs = [];
            foreach(array_diff(scandir($folder), [".", ".."]) as $file) {
                if(isset(pathinfo($folder . "/" . $file)["extension"]) && isset(Config::$formats[pathinfo($folder . "/" . $file)["extension"]])) {
                    // $this->getLogger()->debug(json_encode(Config::$formats) . ": $file");
                    $configs[] = $file;
                }
            }
            return $configs;
        }
        return [];
    }


    /*
    Sends the command sender the permission message
    @param     $sender    \pocketmine\command\CommandSender
    @return void
    */
    public function sendPermissionMessage(\pocketmine\command\CommandSender $sender) {
        $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));
    }


}
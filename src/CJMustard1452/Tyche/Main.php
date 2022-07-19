<?php

namespace CJMustard1452\Tyche;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{

	public $invArray;

	public function onEnable() :Void{
		if(!file_exists($this->getDataFolder() . "InvContents")){
		new Config($this->getDataFolder() . "InvContents", Config::JSON);}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->invArray = json_decode(file_get_contents($this->getDataFolder() . "InvContents"), true);
	}
	public function saveContents(Player $player, String $killer, String $date){
		$this->invArray[$player->getName()]["DeathInfo"] = "Reason of death: $killer | Time: $date EST";
		$this->invArray[$player->getName()]["InvContents"] = $player->getInventory()->getContents();
		$this->invArray[$player->getName()]["ArmorInventoryContents"] = $player->getPlayer()->getArmorInventory()->getContents();
		file_put_contents($this->getDataFolder() . "InvContents", json_encode($this->invArray));
	}
	public function restoreContents(Player $player){
		try{
				$this->getServer()->getPlayer($player->getName())->getInventory()->clearAll();
				$this->getServer()->getPlayer($player->getName())->getArmorInventory()->clearAll();
				if(isset($this->invArray[$player->getName()]["InvContents"])){
					$this->getServer()->getPlayer($player->getName())->getInventory()->setContents($this->invArray[$player->getName()]["InvContents"]);
				}
				if(isset($this->invArray[$player->getName()]["ArmorInventoryContents"])){
					$this->getServer()->getPlayer($player->getName())->getArmorInventory()->setContents($this->invArray[$player->getName()]["ArmorInventoryContents"]);
				}
				unset($this->invArray[$player->getName()]);
				file_put_contents($this->getDataFolder() . "InvContents", json_encode($this->invArray));
				$player->sendMessage("§8(§3Tyche§8) §7Your last cached inventory has been restored.");
		}catch(\Throwable $e){
			return true;
		}
		return true;
	}
	public function onDeath(PlayerDeathEvent $event){
		date_default_timezone_set('America/New_York');
		if($event->getPlayer()->getLastDamageCause() instanceof EntityDamageByEntityEvent){
			$this->saveContents($event->getPlayer(), $event->getPlayer()->getLastDamageCause()->getDamager()->getName(), date('r', time()));
		}else{
			$this->saveContents($event->getPlayer(), 'Unknown', date('r', time()));
		}
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0])){
			if(strtolower($args[0]) == 'restore' || strtolower($args[0]) == 'refund'){
				if(isset($args[1]) && $this->getServer()->getPlayer($args[1])){
					if(isset($this->invArray[$this->getServer()->getPlayer($args[1])->getName()])){
						$this->restoreContents($this->getServer()->getPlayer($args[1]));
						$sender->sendMessage("§8(§3Tyche§8) §7Restored " . $this->getServer()->getPlayer($args[1])->getName() . "'s last cached inventory.");
					}else{
						$sender->sendMessage("§8(§3Tyche§8) §c" . $this->getServer()->getPlayer($args[1])->getName() . "§7 does not have a cached inventory (likely due to a server restart).");
					}
				}else{
					$sender->sendMessage("§8(§3Tyche§8) §7Please list an §cONLINE §7player.");
				}
			}elseif(strtolower($args[0]) == 'info'){
				if(isset($args[1])){
					if(isset($this->invArray[$args[1]]["DeathInfo"])){
						$sender->sendMessage("§8(§3Tyche§8) §7" . $this->invArray[$args[1]]["DeathInfo"]);
					}else{
						$sender->sendMessage("§8(§3Tyche§8) §7There is no information logged under the name §c$args[1]§7.");
					}
				}else{
					$sender->sendMessage("§8(§3Tyche§8) §7Please list a player name.");
				}
			}else{
				$sender->sendMessage("§8(§3Tyche§8) §c". $args[0] . " §7is not a valid §3Tyche §7command, please run §3/tyche help");
			}
		}else{
			$sender->sendMessage("§8(§3Tyche§8) §7Commands: Refund/Restore - Info - Help");
		}
		return true;
	}
	public function onDisable(){
		unlink($this->getDataFolder() . "InvContents");
		return true;
	}
}

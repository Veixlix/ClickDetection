<?php
namespace Ignacio\IgnacioCPSAlert;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase{

	public $config;

	public function onEnable()
	{

		$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, [
			"alert-cps" => 19,
			"kick-cps" => 29,
			"check-interval" => 1,
			"kick-message" => "You have been kicked for cheating",
			"alert-message" => "{player} might be cheating [{cps}]!",
			"message-for-admins" => "{player} was kicked for cheating"
		]);
		$this->config->save();
		$this->getScheduler()->scheduleRepeatingTask(new CheckTask($this), $this->config->get("check-interval") * 20);
	}

	public function getCFG() : Config{
		return $this->config;
	}

	public function getPreciseCPSCounter() : \luca28pet\PreciseCpsCounter\Main{
		/** @var \luca28pet\PreciseCpsCounter\Main $precise */
		$precise = $this->getServer()->getPluginManager()->getPlugin("PreciseCpsCounter");
		return $precise;
	}
}
class CheckTask extends Task{

	public $main;

	public function __construct(Main $main)
	{
		$this->main = $main;
	}

	public function getPreciseCPSCounter(){
		return $this->main->getPreciseCPSCounter();
	}

	public function onRun(int $currentTick)
	{
		$playersWithPermission = [];
		foreach ($this->main->getServer()->getOnlinePlayers() as $player){
			if($player->hasPermission("cps.watch")) $playersWithPermission[] = $player;
			$counter = $this->getPreciseCPSCounter();
			$cps = $counter->getCps($player);
			$alert_cps = $this->main->getCFG()->get("alert-cps");
			$kick_cps = $this->main->getCFG()->get("kick-cps");
			if($cps >= $alert_cps && $cps < $kick_cps){
				foreach ($playersWithPermission as $playerWithPerm){
					$message = $this->main->getCFG()->get("alert-message");
					$str = str_replace(array("{player}", "{cps}"), array($player->getName(), $cps), $message);
					if($playerWithPerm) $playerWithPerm->sendMessage($str);
				}
			}
			if($cps >= $kick_cps){
				$message = $this->main->getCFG()->get("kick-message");
				$player->kick($message, false);
				foreach ($playersWithPermission as $playerWithPerm){
					$message = $this->main->getCFG()->get("message-for-admins");
					$str = str_replace("{player}", $player->getName(), $message);
					if($playerWithPerm) $playerWithPerm->sendMessage($str);
				}
			}
		}
	}
}


<?php

/*
 * @copyright KurSkyTR 2019-2021
 */

namespace KurSkyTR;

use pocketmine\{
    plugin\PluginBase,
    command\CommandSender,
    command\Command,
    utils\Config,
    event\Listener,
    event\player\PlayerJoinEvent,
    player\Player
};

use FormAPI\{
    CustomForm,
    Form
};

class Tpa extends PluginBase implements Listener
{
    
    public static $config = null, $cfg = null;
    
    public function onEnable(): void
    {
        $this->getLogger()->info("Eklenti aktif edildi.");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        self::$cfg = new Config($this->getDataFolder() . "blockedworlds.yml", Config::YAML, [
            "BlockedWorlds" => [
                "nether",
                "end"
            ]
        ]);
    }
    
    public function onJoin(PlayerJoinEvent $event): void
    {
        $e = $event->getPlayer();
        self::$config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            $e->getName() => true
        ]);
    }
    
    public function onCommand(CommandSender $e, Command $kmt, string $lbl, array $args): bool
    {
        if ($kmt->getName() == "tpa")
        {
            if ($e instanceof Player)
            {
                $this->tpa($e);
            }else{
                $e->sendMessage("§cBu komut oyunda kullanılabilir.");
            }
        }
        if ($kmt->getName() == "tpak")
        {
            if ($e instanceof Player)
            {
                if (isset($this->istek[$e->getName()]))
                {
                    $o = $this->getServer()->getPlayer($this->istek[$e->getName()]);
                    if ($o instanceof Player)
                    {
                        $o->teleport($e);
                        $e->sendMessage("§6" . $o->getName() . " §aadlı oyuncunun ışınlanma isteği kabul edildi.");
                        $o->sendMessage("§6" . $e->getName() . " §aadlı oyuncu ışınlanma isteğinizi kabul etti.");
                        $this->istek[$e->getName()] = null;
                    }else{
                        $e->sendMessage("§cIşınlanma isteği gönderen oyuncu bulunamadı.");
                        $this->istek[$e->getName()] = null;
                    }
                }else{
                    $e->sendMessage("§cHerhangi bir ışınlanma isteği bulunmamakta.");
                }
            }else{
                $e->sendMessage("§cBu komut oyunda kullanılabilir.");
            }
        }
        if ($kmt->getName() == "tpar")
        {
            if ($e instanceof Player)
            {
                if (isset($this->istek[$e->getName()]))
                {
                    $o = $this->getServer()->getPlayer($this->istek[$e->getName()]);
                    if ($o instanceof Player)
                    {
                        $e->sendMessage("§6" . $o->getName() . " §cadlı oyuncunun ışınlanma isteği red edildi.");
                        $o->sendMessage("§6" . $e->getName() . " §cadlı oyuncu ışınlanma isteğinizi red etti.");
                        $this->istek[$e->getName()] = null;
                    }else{
                        $e->sendMessage("§cIşınlanma isteği gönderen oyuncu bulunamadı.");
                        $this->istek[$e->getName()] = null;
                    }
                }else{
                    $e->sendMessage("§cHerhangi bir ışınlanma isteği bulunmamakta.");
                }
            }else{
                $e->sendMessage("§cBu komut oyunda kullanılabilir.");
            }
        }
        if ($kmt->getName() == "tpaistek")
        {
            if ($e instanceof Player)
            {
                $this->tpaSettings($e);
            }else{
                $e->sendMessage("§cBu komut oyunda kullanılabilir.");
            }
        }
        return true;
    }
    
    public function tpa($e)
    {
        $f = new CustomForm(function (Player $e, array $data = null)
        {
            if ($data == null) return true;
            if (!$data[0] == null)
            {
                if ($o = $this->getServer()->getPlayer($data[0]))
                {
                    if (self::$config->get($o->getName()) == null)
                    {
                        if ($o->getName() == $e->getName())
                        {
                            $e->sendMessage("§cKendinize ışınlanma isteği gönderemezsiniz.");
                            return false;
                        }
                        $e->sendMessage("§6" . $o->getName() . " §cadlı oyuncu ışınlanma isteklerini kapatmış.");
                        return false;
                    }
                    foreach (self::$cfg->get("BlockedWorlds") as $worlds)
                    {
                        if ($o->getLevel()->getFolderName() == $worlds)
                        {
                            $e->sendMessage("§cBu oyuncu, şuanda §6" . $o->getLevel()->getFolderName() . " §cadlı dünyada. Buraya gidilmesi yasaktır.");
                            return false;
                        }
                    }
                    if ($o->getName() == $e->getName())
                    {
                        $e->sendMessage("§cKendinize ışınlanma isteği gönderemezsiniz.");
                        return false;
                    }
                    $this->istek[$o->getName()] = $e->getName();
                    $e->sendMessage("§6" . $o->getName() . " §aadlı oyuncuya ışınlanma isteği gönderildi.");
                    $o->sendMessage("§6" . $e->getName() . " §3adlı oyuncu size ışınlanma isteği gönderdi.\n§aKabul etmek için : /tpak\n§cRed etmek için : /tpar\n§4§lNOT:§r §eAyrıca ışınlanma isteklerinin gelmesini istemiyorsanız /tpaistek yazarak tpa isteklerini Açık - Kapalı olarak değiştirebilirsin.");
                }else{
                    $e->sendMessage("§cOyuncu bulunamadı.");
                }
            }
        });
        $f->setTitle("Tpa");
        $f->addInput("\nOyuncu Adı:\n", "§7Örn; " . $e->getName());
        $f->sendToPlayer($e);
    }
    
    public function tpaRequestCheck($e): string
    {
        self::$config->reload();
        if (self::$config->get($e->getName()) == true)
        {
            return "§aAçık";
        }else{
            return "§cKapalı";
        }
    }
    
    public function tpaSettings($e)
    {
        $f = new CustomForm(function (Player $e, array $data = null){
            if ($data === null)
            {
                return true;
            }
            self::$config->set($e->getName(), $data[1]);
            self::$config->save();
            $e->sendMessage("§aAyarların kaydedildi.");
        });
        $f->setTitle("Tpa Ayarları");
        $f->addLabel("\n§f- §7Buradan tpa isteklerini ayarlayabilirsin.");
        $f->addToggle("Işınlanma İstekleri | " . $this->tpaRequestCheck($e), self::$config->get($e->getName()));
        $f->sendToPlayer($e);
    }
}

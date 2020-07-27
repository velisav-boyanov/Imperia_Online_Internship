<?php


namespace Controller;
use Model\Services\FieldService;
use Model\Services\PlayerService;
use Core\View;

class PlayerController
{
    const MaxPlayerHealth = 4;
    const MinSize = 0;
    const ItemChance = 15;
    const SmallHealth = -1;
    const LargeHealth = -2;
    const YAxis = 'Y';
    const XAxis = 'X';
    const s = "small_health";
    const l = "big_health";
    const r = "radar";


    public function add()
    {
        $result = [
            'success' => false
        ];
        $baseYAxis = 1;
        $x = $_POST['X'] ?? '';
        $y = $baseYAxis ?? '';
        $health = $_POST['Health'] ?? '';
        $fieldId = $_COOKIE['MyFieldId'] ?? '';

        if(
            !$this->validateXAxis($x)
            || !$this->validateSize($y)
            || !$this->validateHealth($health)
            || !$this->validateSize($fieldId)
        )
        {
            $result['msg'] = 'Invalid player parameters';

            return $result;
        }

        $service = new PlayerService();
        $result = $service->savePlayer($x, $y, $fieldId, $health);

        View::render('game_setup');
    }

    public function getById($playerId)
    {
        $result = [
            'success' => false
        ];

        if (!$this->validateSize($playerId)) {
            $result['msg'] = 'Invalid player id';
            return $result;
        }

        $service = new PlayerService();
        $result = $service->getPlayer($playerId);

        return $result;
    }

    public function getAll()
    {
        $service = new PlayerService();
        $result = $service->getAllPlayers();

    }

    private function whereTo($whereTo){
        $player = new PlayerController();
        $array = $player->getById($_COOKIE['MyPlayerId']);
        $elements = $array['player'];
        $x = $elements['X'];
        $y = $elements['Y'];

        switch ($whereTo) {
            //the map is printed upside down
            case "up":
                $result['axis'] = self::XAxis;
                $result['pos'] = $x - 1;
                break;
            case "down":
                $result['axis'] = self::XAxis;
                $result['pos'] = $x + 1;
                break;
            case "left":
                $result['axis'] = self::YAxis;
                $result['pos'] = $y - 1;
                break;
            case "right":
                $result['axis'] = self::YAxis;
                $result['pos'] = $y + 1;
                break;
            case "q":
                $player->useItem(self::SmallHealth, 1);
                $result['axis'] = self::YAxis;
                $result['pos'] = $y;
                break;
            case "e":
                $player->useItem(self::LargeHealth, 0);
                $result['axis'] = self::YAxis;
                $result['pos'] = $y;
                break;
            case "r":
                $slot = new SlotController();
                $slot->findAll();

                $result['axis'] = self::YAxis;
                $result['pos'] = $y;

                $item = new ItemController();
                $item->useItem("radar");

                break;
            default:
                $result['axis'] = self::YAxis;
                $result['pos'] = $y;
                break;
        }

        return $result;
    }

    private function useItem($stat, $small){
        $player = new PlayerController();
        $array = $player->getById($_COOKIE['MyPlayerId']);
        $elements = $array['player'];
        $health = $elements['Health'];

        $item = new ItemController();
        $service = new PlayerService();

        $damage = 0;
        $result = $item->getSlotByFieldAndPlayerId(self::s);

        if($result['success'] == true){
            if($small == 1) {
                $item->useItem(self::s);
            }elseif($small == 0) {
                $item->useItem(self::l);
            }
            $damage = $stat;
        }

        $service->applyDamage($_COOKIE['MyPlayerId'], $damage, $health);
    }

    private function validateSize($size){
        return $size > self::MinSize;
    }

    private function validateXAxis($x){
        $field = new FieldController();
        $result = $field->getById($_COOKIE['MyFieldId']);

        $fieldElements = $result['field'];
        $fieldWidth = $fieldElements['Width'];

        return $x <= $fieldWidth;
    }

    private function validateHealth($health){
        return ($health > self::MinSize && $health <= self::MaxPlayerHealth);
    }

    private function validatePosition($pos, $axis){
        $field = new FieldController();
        $result = $field->getById($_COOKIE['MyFieldId']);

        $fieldElements = $result['field'];
        $fieldX = $fieldElements['Width'];
        $fieldY = $fieldElements['Length'];

        if($axis == 'X') {
            return ($pos <= $fieldX && $pos > 0);
        }
        if($axis == 'Y') {
            return ($pos <= $fieldY && $pos > 0);
        }
    }

    private function validateWin($pos, $axis){
        $field = new FieldController();
        $array1 = $field->getById($_COOKIE['MyFieldId']);

        $fieldElements = $array1['field'];
        $fieldX = $fieldElements['End_X'];
        $fieldY = $fieldElements['End_Y'];

        $player = new PlayerController();
        $array2 = $player->getById($_COOKIE['MyPlayerId']);
        $elements = $array2['player'];
        $x = $elements['X'];
        $y = $elements['Y'];

        if(($x == $fieldX
        || ($axis == 'X'
        && $pos == $fieldX))
        && ($y == $fieldY
        || ($axis == 'Y'
        && $pos == $fieldY))){
            $this->endGame();

            return 1;
        }
    }

    private function scan(){
        $slot = new SlotController();

        $field = new FieldController();
        $result = $field->getById($_COOKIE['MyFieldId']);

        $fieldElements = $result['field'];
        $x = $fieldElements['Width'];
        $y = $fieldElements['Length'];

        for($i = 1; $i <= $x; $i++){
            for($k = 1; $k <= $y; $k++){
                $slot->setRadar($i, $k);
            }
        }
    }

    public function move(){
        $this->scan();

        $slot = new SlotController();
        $service = new PlayerService();

        $whereTo = $this->whereTo($_POST['Input']);
        $whichPlayer = $_COOKIE['MyPlayerId'];


        if(!$this->validatePosition($whereTo['pos'], $whereTo['axis'])){
            $result['msg'] = 'Out of bounds.';

            echo json_encode($result, JSON_PRETTY_PRINT);
            return $result;
        }

        $result = $service->move($whereTo, $whichPlayer);

        if($this->validateWin($whereTo['pos'], $whereTo['axis']) == 1){
            $result['msg'] = 'You won.';

            $slot->removeSlots();
            echo json_encode($result, JSON_PRETTY_PRINT);
            return $result;
        }

        $this->applyDamage();

        if($this->isDead() == 1){
            $result['msg'] = 'YOU DIED.';
            $slot->removeSlots();

            echo json_encode($result, JSON_PRETTY_PRINT);
            return $result;
        }

        $slot->emptyBomb();
        $slot->find();

        View::render('game');
    }

    private function endGame(){
        $service = new PlayerService();

        $whichPlayer = $_COOKIE['MyPlayerId'];

        $service->endGame($whichPlayer);
    }

    private function applyDamage(){
        $service = new PlayerService();

        $player = new PlayerController();
        $array = $player->getById($_COOKIE['MyPlayerId']);
        $elements = $array['player'];
        $health = $elements['Health'];
        $x = $elements['X'];
        $y = $elements['Y'];

        $slot = new SlotController();
        $damageSlot = $slot->getDamageByFieldXY($_COOKIE['MyFieldId'], $x, $y);
        $damage = $damageSlot['Damage'];

        $this->addItem($damage);

        $service->applyDamage($_COOKIE['MyPlayerId'], $damage, $health);
    }

    private function addItem($damage){
        $player = new PlayerController();
        $playerInfo = $player->getById($_COOKIE['MyPlayerId']);

        $slot = new ItemController();
        $item = new SlotController();
        $result = $item->getDamageByFieldXY($_COOKIE['MyFieldId'], $playerInfo['player']['X'], $playerInfo['player']['Y']);

        if($result['Found'] == 0) {
            if ($damage == 0) {
                $random1 = mt_rand(1, 100);
                $random2 = mt_rand(1, 30);
                if ($random1 < self::ItemChance) {
                    if ($random2 < 30) {
                        $name = self::s;
                    }
                    if ($random2 < 15) {
                        $name = self::l;
                    }
                    if ($random2 == 30 || $random2 == 20 || $random2 == 10) {
                        $name = self::r;
                    }
                    $slot->add($name);
                }
            }
        }
    }

    private function isDead(){
        $player = new PlayerController();
        $array = $player->getById($_COOKIE['MyPlayerId']);
        $elements = $array['player'];
        $health = $elements['Health'];

        if($health == 0){
            return 1;
        }
        return 0;
    }

}
<?php


namespace Model\Services;
use Model\Repository\SlotRepository;

class SlotService
{
    public function saveSlot($x, $y, $fieldId, $damage)
    {
        $result = ['success' => false];

        $repo = new SlotRepository();

        $slotToInsert = [
            'X' => $x,
            'Y' => $y,
            'Field_Id' => $fieldId,
            'Damage' => $damage
        ];

        if($repo->saveSlot($slotToInsert))
        {
            $result['success'] = true;
            $result['msg'] = 'Slot successfully added!';
        }
        return $result;
    }

    public function getSlot($slotId)
    {
        $result = [
            'success' => false
        ];

        $repo = new SlotRepository();
        $slot = $repo->getSlotById($slotId);

        if (!$slot) {
            $result['msg'] = 'Slot with id ' . $slotId . ' was not found!';
            return $result;
        }

        $result['success'] = true;
        $result['slot'] = $slot;
        return $result;
    }

    public function getAllSlots()
    {
        $result = [
            'success' => false
        ];

        $repo = new SlotRepository();
        $slot = $repo->getAllSlots();

        if (!$slot) {
            $result['msg'] = 'There are no slots yet!';
            return $result;
        }

        $result['success'] = true;
        $result['slot'] = $slot;
        return $result;
    }

    public function getDamageByFieldXY($fieldId, $x, $y)
    {
        $repo = new SlotRepository();
        $damage = $repo->getDamageByFieldXY($fieldId, $x, $y);

        if (!$damage) {
            $result['msg'] = 'No such slot exists.';
            return $result;
        }
        $result = $damage;
        return $result;
    }

    public function find($slotId)
    {
        $repo = new SlotRepository();
        $repo->find($slotId);
    }

    public function emptyBomb($slotId){
        $repo = new SlotRepository();
        $repo->emptyBomb($slotId);
    }

    public function removeSlots($fieldId){
        $repo = new SlotRepository();
        $repo->removeSlots($fieldId);
    }

    public function setRadar($radar, $slotId){
        $repo = new SlotRepository();
        $repo->setRadar($radar, $slotId);
    }
}
<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;

class GroupService
{
    public function createGroup($data, $adminId){
        return Group::create([
            'name' => $data->name,
            'admin_id' => $adminId,
        ]);
    }

    public function findGroupById($groupId){
        return Group::find($groupId);
    }

    public function findGroupMemberByGroupId($groupId){
        return GroupMember::where('group_id', $groupId);
    }

    public function findGroupMessagesByGroupId($groupId){
        return GroupMessage::where('group_id', $groupId);
    }

    public function fetchAllGroups(){
        return Group::with('admin')->get();
    }

    public function createGroupMember($groupId, $userId){
        return GroupMember::create([
            'group_id' => $groupId,
            'user_id' => $userId,
        ]);
    }

    public function createGroupMessage($groupId, $userId, $message){
        return GroupMessage::create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    public function findGroupMessageById($messageId){
        return GroupMessage::find($messageId);
    }
}
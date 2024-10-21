<?php

namespace App\Http\Controllers\Api\Functions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GroupService;
use App\Services\ActivityService;

class GroupController extends Controller
{
    protected $groupService;
    protected $activityService;

    
    public function __construct(groupService $groupService, ActivityService $activityService){
        $this->groupService = $groupService;
        $this->activityService = $activityService;
    }

    // Admin creates a group
    public function createGroup(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:groups,name',
            ]);

            $createGroup = $this->groupService->createGroup($request, auth()->user()->id);

            if(!$createGroup){
                return $this->sendResponse(false, 'failed to create a Group', ['data'=> $createGroup], 400);
            }

            $email = auth()->user()->email;
            $title = 'Group Created';
            $action = "A Group of name $createGroup->name and id of $createGroup->id was created.";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'successully created a Group', ['data'=> $createGroup], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating a group', ['error' => $e->getMessage()], 500);
        }
    }

    // Admin deletes a group
    public function deleteGroup(Request $request)
    {
        try {
           $groupId = $request->route('groupId');

            if(!is_numeric($groupId)){
                return $this->sendResponse(false, 'the groupId should be of type int', [], 400);
            }

            $group = $this->groupService->findGroupById($groupId);

            if(!$group){
                return $this->sendResponse(false, 'No data found with the related id', [], 400);
            }

            $groupMembers = $this->groupService->findGroupMemberByGroupId($groupId);
            $groupMessages = $this->groupService->findGroupMessagesByGroupId($groupId);

            $groupMembers->delete();
            $groupMessages->delete();

            $group->delete();

            $email = auth()->user()->email;
            $title = 'Group and Related data Deleted';
            $action = "A Group of name $group->name and it's related data was deleted.";

            $this->activityService->createActivity($email, $title, $action);
            
            return $this->sendResponse(true, 'Group deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating a group', ['error' => $e->getMessage()], 500);
        }
    }

    public function viewAllGroups()
    {
        try {
            $groups = $this->groupService->fetchAllGroups();

            if(!$groups){
                return $this->sendResponse(flase, 'failed to fetch all the groups', [], 400);
            }

            return $this->sendResponse(true, 'successfully fetched all the groups', ['data'=>$groups], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching all the groups', ['error' => $e->getMessage()], 500);
        }
    }

    // User joins a group
    public function joinGroup(Request $request)
    {
        try {
            $groupId = $request->route('groupId');

            if(!is_numeric($groupId)){
                return $this->sendResponse(false, 'group id should be of type numeric', [], 400);
            }

            $group = $this->groupService->findGroupById($groupId);

            if(!$group){
                return $this->sendResponse(false, 'no group found with this id');
            }

            if ($group->members()->where('user_id', auth()->user()->id)->exists()) {
                return $this->sendResponse(false, 'you are already a member of this group .', [], 400);
            }

            $userId = auth()->user()->id;

            $joinGroup = $this->groupService->createGroupMember($groupId, $userId);

            if(!$joinGroup){
                return $this->sendResponse(false, 'failed to join Group', [], 400);
            }

            return $this->sendResponse(true, 'successfully joined Group', ['data'=>$group], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to join a group', ['error' => $e->getMessage()], 500);
        }
    }

    // User sends a message to a group
    public function sendMessage(Request $request)
    {
        try {
            $groupId = $request->route('groupId');

            if(!is_numeric($groupId)){
                return $this->sendResponse(false, 'the group id should be a type if int', [], 400);
            } 
            
            $request->validate([
                'message' => 'required|string',
            ]);

            $group = $this->groupService->findGroupById($groupId);

            if(!$group){
                return $this->sendResponse(false, 'no group found with this id', [], 400);
            }

            if ($group->members()->where('user_id', auth()->user()->id)->exists()) {
                return $this->sendResponse(false, 'you not a member of this group .', [], 400);
            }

            $userId = auth()->user()->id;
            $message = $request->message;

            $message = $this->groupService->createGroupMessage($groupId, $userId, $message);

            if(!$message){
                return $this->sendResponse(false, 'unable to create/send message', [], 400);
            }

            return $this->sendResponse(true, 'successfully sent/created a message in the group', ['data'=>$message], 201);
        }  catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to send message to group', ['error' => $e->getMessage()], 500);
        }
    }

    // View messages in a group
    public function viewMessages(Request $request)
    {
        try {
            $groupId = $request->route('groupId');

            if(!is_numeric($groupId)){
                return $this->sendResponse(false, 'the group id should be a type if int', [], 400);
            } 

            $group = $this->groupService->findGroupById($groupId);

            if(!$group){
                return $this->sendResponse(false, 'no group found with this id', [], 400);
            }

            if (!$group->members()->where('user_id', auth()->user()->id)->exists()) {
                return $this->sendResponse(false, 'You are not a member of this group', [], 400);
            }

            $messages = $group->messages()->with('user')->get();

            if(!$messages){
                return $this->sendResponse(false, 'failed to fetch messages', [], 400);
            }

            return $this->sendResponse(true, 'successfully fetched all the messages', ['data'=>$messages], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to veiw message in a group', ['error' => $e->getMessage()], 500);
        }
    }

    // edit messages in a group
    public function editMessage(Request $request, $messageId)
    {
        try {
            $messageId = $request->route('messageId');

            if(!$messageId){
                return $this->sendResponse(false, 'message Id should be a type of INT', [], 400);
            }

            $request->validate([
                'message' => 'required|string',
            ]);

            $message = $this->groupService->findGroupMessageById($messageId);

            if(!$message){
                return $this->sendResponse(false, 'message resource was not found', [], 400);
            }

            if ($message->user_id !== auth()->user()->id) {
                return $this->SendResponse(false, 'You are not authorized to edit this message, you are not the writer', [], 400);
            }

            $message->update([
                'message' => $request->message,
            ]);

            return $this->sendResponse(true, 'edited successfully', ['data'=>$message], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to edit message in a group', ['error' => $e->getMessage()], 500);
        }
    }

    // delete message
    public function deleteMessage(Request $request)
    {
        try {
            $messageId = $request->route('messageId');

            if(!$messageId){
                return $this->sendResponse(false, 'message id should be a type of INT', [], 400);
            }

            $message = $this->groupService->findGroupMessageById($messageId);

            if(!$message){
                return $this->sendResponse(false, 'message resource was not found', [], 400);
            }

            if ($message->user_id !== auth()->user()->id) {
                return $this->SendResponse(false, 'You are not authorized to delete this message, you are not the writer', [], 400);
            }

            $message->delete();

            return $this->sendResponse(false, 'Message deleted successfully.', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to delete message in a group', ['error' => $e->getMessage()], 500);
        }
    }

    public function adminDeleteMessage(Request $request)
    {
        try {
            $messageId = $request->route('messageId');

            if(!$messageId){
                return $this->sendResponse(false, 'message id should be a type of INT', [], 400);
            }

            $message = $this->groupService->findGroupMessageById($messageId);

            if(!$message){
                return $this->sendResponse(false, 'message resource was not found', [], 400);
            }

            $message->delete();

            $email = auth()->user()->email;
            $title = 'Group Message/chat Deleted';
            $action = "A Group chat of id $message->id and the writer's id is $message->user_id";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(false, 'Message deleted successfully.', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to delete message in a group', ['error' => $e->getMessage()], 500);
        }
    }

}

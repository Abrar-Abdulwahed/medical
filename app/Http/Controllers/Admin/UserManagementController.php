<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Enums\UserType;
use Illuminate\Http\Request;
use App\Http\Traits\FileTrait;
use App\Actions\GetUsersDataAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Admin\UserActivationRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserManagementController extends Controller
{
    use FileTrait;
    public function __construct(protected GetUsersDataAction $getUsersAction){}

    public function index(Request $request)
    {
        return $this->getUsersAction->__invoke($request, ['patientProfile', 'serviceProviderProfile']);
    }

    public function patients(Request $request)
    {
        return $this->getUsersAction->__invoke($request, ['patientProfile'], UserType::PATIENT->value);
    }

    public function serviceProviders(Request $request)
    {
        return $this->getUsersAction->__invoke($request, ['serviceProviderProfile'], UserType::SERVICE_PROVIDER->value);
    }

    public function show($id)
    {
        try{
            $user = User::findOrFail($id);
            return $this->returnJSON(new UserResource($user->loadMissing(['patientProfile', 'serviceProviderProfile'])), 'User Data retrieved!');
        } catch (\Exception $e) {
            return $this->returnWrong($e->getMessage());
        }
    }

    public function ServiceProviderAccept(UserActivationRequest $request, $id)
    {
        try{
            $user = User::findOrFail($id);
            $user->forceFill(['activated' => 1])->save();
            return $this->returnSuccess('Service Provider has been activated!');
        } catch (\Exception $e) {
            return $this->returnWrong($e->getMessage());
        }
    }

    public function ServiceProviderRefuse(UserActivationRequest $request, $id)
    {
        try{
            $user = User::findOrFail($id);
            $this->removeDirectory($user->attachment_path);
            $user->delete();
            return $this->returnSuccess('Service Provider has been deleted from database!');
        } catch (\Exception $e) {
            return $this->returnWrong($e->getMessage());
        }
    }
}

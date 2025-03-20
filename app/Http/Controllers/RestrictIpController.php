<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestrictIp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;


class RestrictIpController extends Controller
{

    public function index(Request $request)
    {
        if(\Auth::user()->can('manage employee'))
        {
            if(Auth::user()->type == 'Employee')
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $restricted_ip = RestrictIp::where('created_by', \Auth::user()->creatorId())->get();

            return view('restrictip.index', compact('restricted_ip'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create employee'))
        {
            return view('restrictip.create');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create employee'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'ip' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->withInput()->with('error', $messages->first());
            }
            $ip = new RestrictIp();
            $ip->ip = $request->ip;
            $ip->created_by = \Auth::user()->creatorId();

            if($ip->save())
            {
                return redirect()->route('restrict-ip.index')->with('success', __('New IP Added Successfully.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }else{
                return redirect()->back()->with('error', __('Something went wrong.'));
            }

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        if(\Auth::user()->can('create employee'))
        {   
            $ip = RestrictIp::find($id);
            return view('restrictip.edit',compact('ip'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if(\Auth::user()->can('edit employee'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'ip' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $ip = RestrictIp::findOrFail($id);
            $ip->ip = $request->ip;
            $ip->created_by = \Auth::user()->creatorId();

            if($ip->save())
            {
                return redirect()->route('restrict-ip.index')->with('success', __('IP Updated Successfully.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }else{
                return redirect()->back()->with('error', __('Something went wrong.'));
            }

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {

        if(Auth::user()->can('delete employee'))
        {
            $ip      = RestrictIp::findOrFail($id);
            $ip->delete();

            return redirect()->route('restrict-ip.index')->with('success', 'IP successfully deleted.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }
}

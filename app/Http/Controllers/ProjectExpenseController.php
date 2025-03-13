<?php

namespace App\Http\Controllers;

use App\Models\ProjectExpense;
use App\Models\Project;
use App\Models\Utility;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\TransactionLines;
use App\Models\Vender;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class ProjectExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($project_id)
    {
        if(\Auth::user()->can('manage project expense'))
        {
            $project     = Project::find($project_id);
            $amount      = $project->expense->sum('amount');
            $expense_cnt = Utility::projectCurrencyFormat($project_id, $amount) . '/' . Utility::projectCurrencyFormat($project_id, $project->budget);

            return view('project_expense.index', compact('project', 'expense_cnt'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($project_id)
    {
        if(\Auth::user()->can('create project expense'))
        {
            $project = Project::find($project_id);
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id'))
                ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
                ->where('chart_of_account_types.name' ,'Expenses')
                ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chart_accounts->prepend('Select Account', '');
            return view('project_expense.create', compact('project', 'accounts', 'chart_accounts', 'vender'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function getTasksByMilestone($projectId, $milestoneId)
{
    if ($milestoneId == 0) {
        $tasks = ProjectTask::where('project_id', $projectId)->pluck('name', 'id');
    } else {
        $tasks = ProjectTask::where('milestone_id', $milestoneId)->pluck('name', 'id');
    }
    
    return response()->json($tasks);
}


    public function store(Request $request, $project_id)
    {
        if(\Auth::user()->can('create project expense'))
        {
            $usr       = \Auth::user();
            $validator = Validator::make(
                $request->all(), [
                                'name' => 'required|max:120',
                                'amount' => 'required|numeric|min:0',
                            ]
            );

            if($validator->fails())
            {
                return redirect()->back()->with('error', Utility::errorFormat($validator->getMessageBag()));
            }

            $post               = $request->all();
            $post['project_id'] = $project_id;
            $post['date']       = (!empty($request->date)) ? date("Y-m-d H:i:s", strtotime($request->date)): null;
            $post['created_by'] = $usr->id;
            $post['account_id'] = $request->account_id;
            $post['chart_accounts'] = $request->chart_accounts;
            $post['milestone_id'] = $request->milestone_id;
            $post['vender_id'] = $request->vender_id;

            if($request->hasFile('attachment'))
            {
                $fileNameToStore    = time() . '.' . $request->attachment->getClientOriginalExtension();
                $path               = $request->file('attachment')->storeAs('expense', $fileNameToStore);
                $post['attachment'] = $path;
            }

            $expense = ProjectExpense::create($post);

            $accountId = BankAccount::find($request->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $request->amount,
                'reference' => 'Project Expense',
                'reference_id' => $expense->id,
                'reference_sub_id' => 0,
                'date' => $request->date,
            ];
            Utility::addTransactionLines($data, "new");

            $data = [
                'account_id' => $request->chart_accounts,
                'transaction_type' => 'Debit',
                'transaction_amount' => $request->amount,
                'reference' => 'Project Expense',
                'reference_id' => $expense->id,
                'reference_sub_id' => 0,
                'date' => $request->date,
            ];
            Utility::addTransactionLines($data, "new");

            Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

            // Make entry in activity log
            ActivityLog::create(
                [
                    'user_id' => $usr->id,
                    'project_id' => $project_id,
                    'log_type' => 'Create Expense',
                    'remark' => json_encode(['title' => $expense->name]),
                ]
            );

            return redirect()->back()->with('success', __('Expense added successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectExpense $projectExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($project_id, $expense_id)
    {
        if(\Auth::user()->can('edit expense'))
        {
            $project = Project::find($project_id);
            $expense = ProjectExpense::find($expense_id);
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id'))
                ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
                ->where('chart_of_account_types.name' ,'Expenses')
                ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chart_accounts->prepend('Select Account', '');
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');
            return view('project_expense.edit', compact('project', 'expense', 'accounts', 'chart_accounts', 'vender'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $project_id, $expense_id)
    {
        if(\Auth::user()->can('edit project expense'))
        {
            $validator = Validator::make(
                $request->all(), [
                                'name' => 'required|max:120',
                                'amount' => 'required|numeric|min:0',
                            ]
            );

            if($validator->fails())
            {
                return redirect()->back()->with('error', Utility::errorFormat($validator->getMessageBag()));
            }

            $expense = ProjectExpense::find($expense_id);

            Utility::bankAccountBalance($expense->account_id, $expense->amount, 'credit');

            $expense->name = $request->name;
            $expense->date = date("Y-m-d H:i:s", strtotime($request->date));
            $expense->amount =$request->amount;
            $expense->task_id = $request->task_id;
            $expense->description = $request->description;
            $expense->account_id = $request->account_id;
            $expense->chart_accounts = $request->chart_accounts;
            $expense->milestone_id = $request->milestone_id;
            $expense->vender_id = $request->vender_id;

            if($request->hasFile('attachment'))
            {
                Utility::checkFileExistsnDelete([$expense->attachment]);

                $fileNameToStore    = time() . '.' . $request->attachment->extension();
                $path =  $request->file('attachment')->storeAs('expense', $fileNameToStore);
                $expense->attachment = $path;
            }

            $expense->save();

            TransactionLines::where("reference", "Project Expense")->where("reference_id", $expense->id)->where('created_by', \Auth::user()->creatorId())->delete();
            $accountId = BankAccount::find($request->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $request->amount,
                'reference' => 'Project Expense',
                'reference_id' => $expense->id,
                'reference_sub_id' => 0,
                'date' => $request->date,
            ];
            Utility::addTransactionLines($data, "new");

            $data = [
                'account_id' => $request->chart_accounts,
                'transaction_type' => 'Debit',
                'transaction_amount' => $request->amount,
                'reference' => 'Project Expense',
                'reference_id' => $expense->id,
                'reference_sub_id' => 0,
                'date' => $request->date,
            ];
            Utility::addTransactionLines($data, "new");

            Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

            return redirect()->back()->with('success', __('Expense Updated successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($expense_id)
    {
        if(\Auth::user()->can('delete project expense'))
        {
            $expense = ProjectExpense::find($expense_id);
            Utility::bankAccountBalance($expense->account_id, $expense->amount, 'credit');
            TransactionLines::where("reference", "Project Expense")->where("reference_id", $expense_id)->where('created_by', \Auth::user()->creatorId())->delete();
            Utility::checkFileExistsnDelete([$expense->attachment]);
            $expense->delete();

            return redirect()->back()->with('success', __('Expense Deleted successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}

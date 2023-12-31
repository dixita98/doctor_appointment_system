<?php

namespace App\Http\Controllers\patient;

use App\Http\Controllers\Controller;
use App\Models\Appoiment;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class AppoimentController extends Controller
{
    public function list(Request $request)
    {
        $patient = Patient::where('user_id', Auth::user()->id)->first();
        if($request->ajax())
        {
            $data = Appoiment::where('patient_id', $patient->id)->with('doctor', 'patient')->orderBy('date_time','ASC');

                return DataTables::of($data)
                ->addIndexColumn()

                ->editColumn('date', function ($row)
                {
                    return date("d-m-Y - h:i A", strtotime($row->date_time));
                })

                ->addColumn('status', function($row){
                    if($row->status == 'pending')
                    {
                        $btn = '<div style="background: blue; display: inline-block; padding: 0px 5px; font-size: 14px; color: white; font-weight: 600; border-radius: 5px;">'.$row->status.'</div>';
                    }

                    if($row->status == 'approve')
                    {
                        $btn = '<div style="background: green; display: inline-block; padding: 0px 5px; font-size: 14px; color: white; font-weight: 600; border-radius: 5px;">'.$row->status.'</div>';
                    }

                    if($row->status == 'reject')
                    {
                        $btn = '<div style="background: red; display: inline-block; padding: 0px 5px; font-size: 14px; color: white; font-weight: 600; border-radius: 5px;">'.$row->status.'</div>';
                    }

                    return $btn;
                })

                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('patient.appoiment.edit',['id'=>$row->id]).'"><button class="btn-sm btn-success">Edit</button></a>
                            <button onclick="Delete('.$row->id. ')" class="btn-sm btn-danger">Delete</button>
                            <a href="' . route('patient.prescription.add', ['appoiment_id' => $row->id]) . '"><button class="btn-sm btn-primary">Prescription</button></a>';
                    return $btn;
                })

                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return view('patient.appoiment.list');
    }

    public function add(Request $request)
    {
        $patient = Patient::where('user_id', Auth::user()->id)->first();
        if($request->ajax())
        {
            //VALIDATION START
            $rules = array(
                'doctor'  => 'required',
                'date_time'  => 'required'
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {

                $error=json_decode($validator->errors());
                return response()->json(['status' => 401,'error1' => $error]);
            }

            //VALIDATION END

            $form_data = new Appoiment();
            $form_data->patient_id  = $patient->id;
            $form_data->doctor_id   = $request->doctor;
            $form_data->date_time   = $request->date_time;
            $form_data->status   = 'pending';
            $form_data->save();

            $redirect = route('patient.appoiment.list');
			return response()->json(['status' => 1,'redirect' => $redirect]);
        }

        $doctor = Doctor::get();
        return view('patient.appoiment.add')->with(['doctor'=>$doctor]);
    }

    public function edit(Request $request, $id)
    {
        $patient = Patient::where('user_id', Auth::user()->id)->first();
        if($request->ajax())
        {
            //VALIDATION START
            $rules = array(
                'doctor'  => 'required',
                'date_time'  => 'required'
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {

                $error = json_decode($validator->errors());
                return response()->json(['status' => 401, 'error1' => $error]);
            }
            //VALIDATION END

            $form_data = Appoiment::where('id',$request->id)->first();
            $form_data->patient_id  = $patient->id;
            $form_data->doctor_id   = $request->doctor;
            $form_data->date_time   = $request->date_time;
            $form_data->status   = 'pending';
            $form_data->save();

            $redirect = route('patient.appoiment.list');
			return response()->json(['status' => 1,'redirect' => $redirect]);
        }

        $data = Appoiment::where('id',$id)->first();
        if(empty($data))
        {
            return redirect()->back();
        }

        $doctor = Doctor::get();
        return view('patient.appoiment.edit')->with(['data' => $data, 'doctor' => $doctor]);
    }

    public function delete(Request $request, $id)
    {
        $appoiment = Appoiment::where('id',$id)->first();
        $appoiment->destroy($id);
        return response()->json(['status' => 1]);
    }

    public function changestatus(Request $request)
    {
        Appoiment::where('id',$request->id)->update(['status'=>$request->status]);
        return response()->json(['status' => 1]);
    }
}

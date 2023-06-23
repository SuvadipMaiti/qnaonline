<?php

namespace App\Http\Controllers\Api\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Slider;
use App\Category;
use App\Question;
use App\Contact;
use App\Newsletter;
use Illuminate\Support\Facades\Validator;

class SliderApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function slider()
    {
        $sliders = Slider::where('status',1)->get();
        return response()->json([
                    'sliders' => $sliders,
                    'status' => 1
                ],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = Category::where('status',1)->get();
        return response()->json([
                    'categories' => $categories,
                    'status' => 1
                ],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function latest_questions()
    {
        $latest_questions = Question::where('status',1)->orderBy('updated_at')->take(10)->get();
        return response()->json([
                    'latest_questions' => $latest_questions,
                    'status' => 1
                ],200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'heading' => 'required|max:255',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }

        $contact_sub = new Contact;
        $contact_sub->heading = $request->heading;
        $contact_sub->email = $request->email;
        $contact_sub->description = $request->description;
        $contact_sub->status = 1;
        $contact_sub->save();    


        if(@$contact_sub){
            return response()->json([
                'contact' => $contact_sub,
                'success' => 'Form successfully submitted',
                'status' => 1
            ],201);
        }else{
            return response()->json([
                'alert' => 'Form submission failed',
                'status' => 0
            ],400);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletters|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }

        $email_sub = new Newsletter;
        $email_sub->email = $request->email;
        $email_sub->status = 1;
        $email_sub->save();    


        if(@$email_sub){
            return response()->json([
                'contact' => $email_sub,
                'success' => 'Form successfully submitted',
                'status' => 1
            ],201);
        }else{
            return response()->json([
                'alert' => 'Form submission failed',
                'status' => 0
            ],400);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function question($slug)
    {
        $select_question = Question::where('status',1)->where('slug',$slug)->get();
        if(!@$select_question){
            return response()->json([
                    'select_question' => $select_question,
                    'status' => 1
                ],200);
        }
        else
        {
            return response()->json([
                'status' => 0
            ],400);
        }         
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function user_category_questions($slug)
    {
        $category_questions = Category::where('status',1)->where('slug',$slug)->first()->questions()->get();        
        if(!@$category_questions){
            return response()->json([
                    'category_questions' => $category_questions,
                    'status' => 1
                ],200);
        }
        else
        {
            return response()->json([
                'status' => 0
            ],400);
        }        
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

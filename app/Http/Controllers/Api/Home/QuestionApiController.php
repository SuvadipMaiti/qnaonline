<?php

namespace App\Http\Controllers\Api\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Profile;
use App\Question;
use App\Category;
use App\Tag;
use App\Answer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class QuestionApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function user_submit_question(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'category'=>'required|array',
            'tag'=>'required|array',
            'id'=>'required',
            'question'=>'required|min:10|max:191|unique:questions',
            'status'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }

        $question_ins = new Question;
        $question_ins->user_id = $request->id;
        $question_ins->question = $request->question;
        $question_ins->slug = str_slug($request->question);
        $question_ins->description = $request->description;
        $question_ins->status = $request->status;
        $question_ins->save();

        $category = array_column($request->category, 'id');
        $tag = array_column($request->tag, 'id');


        $category_ins = Question::find($question_ins->id)->categories()->attach($category);
        $tag_ins = Question::find($question_ins->id)->tags()->attach($tag);  

        if(@$question_ins){
            return response()->json([
                'question_ins' => $question_ins,
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
    public function user_edit_question_data($id)
    {
        $question = Question::where('id',$id)->with('tags','categories')->first();
        if(@$question){

            return response()->json([
                'question' => $question,
                'status' => 1
            ],200);
        }
        else
        {
            return response()->json([
                'status' => 0,
                'alert' => 'Url not found'
            ],400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function user_edit_question_submit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'question_id'=>'required',
            'question'=>'required|min:10|max:191|unique:questions,question,'.$request->question_id,
            'id'=>'required',           
            'category'=>'required|array',
            'tag'=>'required|array',
            'status'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }


        $question_up = Question::where('id',$request->question_id)->first();
        $question_up->user_id = $request->id;
        $question_up->question = $request->question;
        $question_up->slug = str_slug($request->question);
        $question_up->description = $request->description;
        $question_up->status = $request->status;       
        $question_up->save();


        $category = array_column($request->category, 'id');
        $tag = array_column($request->tag, 'id');

        $category_detach = Question::find($question_up->id)->categories()->detach();
        $tag_detach = Question::find($question_up->id)->tags()->detach();



        $category_up = Question::find($question_up->id)->categories()->attach($category);
        $tag_up = Question::find($question_up->id)->tags()->attach($tag);  


      

        if($question_up){
            return response()->json([
                'question_ins' => $question_up,
                'success' => 'Form successfully updated',
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
    public function user_answer_submit(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'answer'=>'required|max:191|unique:answers',
            'description'=>'required',
            'id'=>'required',
            'status'=>'required',
            'question_id'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }

        $answer_ins = new Answer;
        $answer_ins->user_id = $request->id;
        $answer_ins->question_id = $request->question_id;
        $answer_ins->answer = $request->answer;
        $answer_ins->slug = str_slug($request->answer);
        $answer_ins->description = $request->description;
        $answer_ins->status = $request->status;       
        $answer_ins->save();
 

        if(@$answer_ins){
            return response()->json([
                'question_ins' => $answer_ins,
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function user_edit_answer_data($id)
    {
        $answer = Answer::find($id);
        if($answer){
            return response()->json([
                'answer' => $answer,
                'status' => 1
            ],200);
        }
        else
        {
            return response()->json([
                'status' => 0,
                'alert' => 'Url not found'
            ],400);
        }
    }


         /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function user_edit_answer_submit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'answer_id'=>'required',
            'question_id'=>'required',
            'answer'=>'required|min:10|max:191|unique:answers,answer,'.$request->answer_id,
            'id'=>'required',           
            'description'=>'required',
            'status'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }

        $answer_up = Answer::where('id',$request->answer_id)->first();
        $answer_up->user_id = $request->id;
        $answer_up->question_id = $request->question_id;
        $answer_up->answer = $request->answer;
        $answer_up->slug = str_slug($request->answer);
        $answer_up->description = $request->description;
        $answer_up->status = $request->status;          
        $answer_up->save();

        if($answer_up){
            return response()->json([
                'answer_up' => $answer_up,
                'success' => 'Form successfully updated',
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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

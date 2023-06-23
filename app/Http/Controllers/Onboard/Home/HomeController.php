<?php

namespace App\Http\Controllers\Onboard\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Tag;
use App\Category;
use App\Question;
use App\Check;
use App\Answer;
use App\Visitor;
use App\Course;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use DB;

class HomeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $recent_questions = Question::where('status',1)->orderBy('updated_at','DESC')->take(30)->get();
        $most_view_questions = Question::leftJoin('visitors','questions.id','=','visitors.question_id')->
               selectRaw('questions.id,questions.question,questions.slug,questions.created_at,questions.updated_at, count(visitors.question_id) AS `count`')->
               groupBy('questions.id')->
               orderBy('count','DESC')->
               take(30)->get();

        $questions = Question::where('status',1)->orderBy('created_at','DESC')->simplepaginate(10);
        
        $header_title = "";
        $header_keywords = "";
        $header_description = "";
        foreach($questions as $question){
            $header_title = $header_title." ".strip_tags($question->question);
            if(@$question->tags){
                foreach($question->tags as $t)
                {
                    $header_keywords = $header_keywords." ".strip_tags($t->tag);
                }
            }
            if(@$question->categories){
                foreach($question->categories as $c)
                {
                    $header_keywords = $header_keywords." ".strip_tags($c->category);
                }
            }
            if(@$question->description){
                $header_description = strip_tags($question->description);
            }
            if(@$question->answers){
                foreach($question->answers as $a)
                {
                    $header_description = $header_description." ".strip_tags($a->answer);
                    $header_description = $header_description." ".strip_tags($a->description);
                }
            }
        }
        $header_title = substr($header_title,0,50) . "..."; 
        $header_keywords = substr($header_keywords,0,100) . "..."; 
        $header_description = substr($header_description,0,150) . "..."; 
        return view('onboard.home.home',compact('questions','recent_questions','most_view_questions','header_keywords','header_description','header_title'));

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function question($slug)
    {
        $question = Question::where('slug',$slug)->where('status',1)->first();
        if($question)
        {
            $searchValues = explode("-", $slug); // Split the words

            $relevant_questions = Question::where(function ($q) use ($searchValues) {
              foreach ($searchValues as $value) {
                $q->orWhere('question', 'like', "%{$value}%");
                $q->orWhere('description', 'like', "%{$value}%");
              }
            })->where('status',1)->orderBy('created_at','DESC')->take(30)->get();

            $categories = Category::where('status',1)->take(30)->get();
            $tags = Tag::where('status',1)->take(30)->get();
            $recent_questions = Question::where('status',1)->orderBy('updated_at','DESC')->take(30)->get();


            // get previous user id
            $previous = Question::where('id', '<', $question->id)->max('id');
            $previous_slug = Question::select('slug')->where('id',$previous)->first();
            // get next user id
            $next = Question::where('id', '>', $question->id)->min('id');
            $next_slug = Question::select('slug')->where('id',$next)->first();

                $header_title = "";
                $header_keywords = "";
                $header_description = "";
                if(@$question->tags){
                    $header_title = strip_tags($question->question);
                    foreach($question->tags as $t)
                    {
                        $header_keywords = $header_keywords." ".strip_tags($t->tag);
                    }
                }
                if(@$question->categories){
                    foreach($question->categories as $c)
                    {
                        $header_keywords = $header_keywords." ".strip_tags($c->category);
                    }
                }
                if(@$question->description){
                    $header_description = strip_tags($question->description);
                }
                if(@$question->answers){
                    foreach($question->answers as $a)
                    {
                        $header_description = $header_description."...".strip_tags($a->answer);
                        $header_description = $header_description."...".strip_tags($a->description);
                    }
                }
                $header_title = substr($header_title,0,50) . "..."; 
                $header_keywords = substr($header_keywords,0,100) . "..."; 
                $header_description = substr($header_description,0,150) . "..."; 
            return view('onboard.home.question',compact('question','previous_slug','tags','categories','recent_questions','next_slug','relevant_questions','header_keywords','header_description','header_title'));
        }
        else
        {
            return redirect()->route('onboard');
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function onboard_ans_check(Request $request)
    {
        $answer = Answer::find($request->ansId);
        $status = 0;
        if($answer){
            $clientIP = request()->ip();
            $check = Check::where('ip_address',$clientIP)->where('answer_id',$answer->id)->first();
            if(!@$check)
            {
                $check_ins = new Check;
                $check_ins->ip_address = $clientIP;
                $check_ins->answer_id = $answer->id;
                $check_ins->save();
                
            }else{
                $check->delete();
            }

            $status = 1;
        }

        $answer = Answer::find($request->ansId);
        $anscount = $answer->checks->count();    

        $question = Question::find($request->QuId);
        $qucount = 0;
        foreach($question->answers as $answer)
        {
            $qucount+= $answer->checks->count();
        }
                    
        $result = [
            'status'=> $status,
            'anscount' => $anscount,
            'qucount' => $qucount
        ];
        return $result;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function onboard_ans_visit(Request $request)
    {   
        $clientIP = request()->ip();
        $answersId = explode(",",$request->answerIdsVisit);
        $questionId = $request->questionIdVisit;
        $status = 0;
        foreach($answersId as $answerId)
        {
            $visitor = Visitor::where('ip_address',$clientIP)->where('question_id',$questionId)->where('answer_id',$answerId)->first();
            if(!@$visitor)
            {
                $visitor_ins = new Visitor;
                $visitor_ins->ip_address = $clientIP;
                $visitor_ins->question_id = $questionId;
                $visitor_ins->answer_id = $answerId;
                $visitor_ins->save(); 

                $status = 1; 
            }
        }
        $question = Question::find($questionId);
        $count = $question->visitors->count();
        $result = [
            'status'=> $status,
            'count'=> $count
        ];
        return $result;
    }


}

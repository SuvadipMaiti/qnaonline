<?php

namespace App\Http\Controllers\Api\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Question;
use App\Tag;
use App\Category;
use App\Visitor;
use App\Answer;
use App\Course;
use App\Check;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendRegMail;


class HomeApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $questions = Question::where('status',1)->orderBy('updated_at','DESC')->with('user','answersActive','user.profile','categories.questions','tags.questions','visitors','answers.checks')->paginate(10);
        
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

        return response()->json([
                    'questions' => $questions,
                    'header_keywords' => $header_keywords,
                    'header_description' => $header_description,
                    'header_title' => $header_title,
                    'status' => 1
                ],200);
    }


            /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function user_search_question($type,$search = null)
    {


        // split on 1+ whitespace & ignore empty (eg. trailing space)
        $searchValues = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY); 

        if($type == 'search'){

          $questions = Question::where(function ($q) use ($searchValues) {
            foreach ($searchValues as $value) {
              $q->orWhere('question', 'like', "%{$value}%");
              $q->orWhere('description', 'like', "%{$value}%");
            }
          })->where('status',1)
          ->orderBy('updated_at','DESC')
          ->with('user','answersActive','user.profile','categories.questions','tags.questions','visitors','answers.checks')
          ->paginate(10);

        }else if($type == 'category'){

               $questions = Question::join('category_question', 'questions.id', '=', 'category_question.question_id')
               ->join('categories', 'categories.id', '=', 'category_question.category_id')
               ->select(
                   'questions.*'
               )->
               with('user','user.profile','categories.questions','tags.questions','visitors','answers.checks')->
               groupBy('questions.id')->
               where('categories.category','like',"%{$search}%")->
               where('questions.status',1)->
               orderBy('questions.updated_at','DESC')->
               paginate(10);
        }else if($type == 'tag'){

               $questions = Question::join('tag_question', 'questions.id', '=', 'tag_question.question_id')
               ->join('tags', 'tags.id', '=', 'tag_question.tag_id')
               ->select(
                   'questions.*'
               )->
               with('user','user.profile','categories.questions','tags.questions','visitors','answers.checks')->
               groupBy('questions.id')->
               where('tags.tag','like',"%{$search}%")->
               where('questions.status',1)->
               orderBy('questions.updated_at','DESC')->
               paginate(10);
        }

        return response()->json([
                    'questions' => $questions,
                    'status' => 1
                ],200);      
        
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function user_questions($user_id)
    {

        $questions = Question::where('user_id',$user_id)
                        ->orderBy('updated_at','DESC')
                        ->with('user','user.profile','categories.questions','tags.questions','visitors','answers.checks')
                        ->paginate(10);

        if($questions)
        {
            return response()->json([
                  'questions' => $questions,
                  'status' => 1
              ],200);
        }else
        {
            return response()->json([
                'status' => 0,
                'alert' => 'User not found'
            ],400);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function relevant_post($slug)
    {


        // split on 1+ whitespace & ignore empty (eg. trailing space)
        // $searchValues = preg_split('/\s+/', $slug, -1, PREG_SPLIT_NO_EMPTY); 
        $searchValues = explode("-", $slug); // Split the words

        $relevant_questions = Question::where(function ($q) use ($searchValues) {
          foreach ($searchValues as $value) {
            $q->orWhere('question', 'like', "%{$value}%");
            $q->orWhere('description', 'like', "%{$value}%");
          }
        })->where('status',1)->orderBy('updated_at','DESC')->paginate(10);

        return response()->json([
            'relevant_questions' => $relevant_questions,
            'status' => 1
        ],200);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function recent_post()
    {
        $recent_questions = Question::select('id','question','slug','created_at','updated_at')->where('status',1)->orderBy('updated_at','DESC')->paginate(10);
        return response()->json([
            'recent_questions' => $recent_questions,
            'status' => 1
        ],200);
    }


        /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function courses()
    {
        $courses = Course::where('status',1)->orderBy('updated_at','DESC')->with('question')->get();
        return response()->json([
            'courses' => $courses,
            'status' => 1
        ],200);
    }


        /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function tags()
    {
        $tags = Tag::select('tag')->where('status',1)->orderBy('updated_at','DESC')->paginate(10);
        return response()->json([
            'tags' => $tags,
            'status' => 1
        ],200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function tags_all()
    {
        $tags = Tag::where('status',1)->get();
        return response()->json([
            'tags_all' => $tags,
            'status' => 1
        ],200);
    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = Category::select('category')->where('status',1)->orderBy('updated_at','DESC')->paginate(10);
        
        return response()->json([
            'categories' => $categories,
            'status' => 1
        ],200);
    }
     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories_all()
    {
        $categories = Category::where('status',1)->get();
        
        return response()->json([
            'categories_all' => $categories,
            'status' => 1
        ],200);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function most_view_post()
    {

        // $most_view_questions = Question::select('id')->where('status',1)->get()->sortByDesc(function($visitors)
        // {
        //     return $visitors->visitors->count();
        // });


        $most_view_questions = Question::leftJoin('visitors','questions.id','=','visitors.question_id')->
               selectRaw('questions.id,questions.question,questions.slug,questions.created_at,questions.updated_at, count(visitors.question_id) AS `count`')->
               groupBy('questions.id')->
               orderBy('count','DESC')->
               paginate(10);



        return response()->json([
            'most_view_questions' => $most_view_questions,
            'status' => 1
        ],200);
    }
    


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $authid = 2;
        $relevant_questions = Answer::where('question_id',$id)->where(function ($a) use ($authid) {
            $a->orWhere('status', 1);
        })->with(['user'=> function($query) use($authid){
            $query->orWhere('id',$authid);
        },'question.user'=>function($query) use($authid){
            $query->orWhere('id',$authid);
        }])->orderBy('updated_at','ASC')->paginate(1);


        return $relevant_questions;
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
    public function question($slug)
    {

        $question = Question::where('slug',$slug)->with('user','user.profile','answers.user.profile','answers.checks','answersActive')->first();
        if($question){
            
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

            return response()->json([
                'question' => $question,
                'header_title' => $header_title,
                'header_keywords' => $header_keywords,
                'header_description' => $header_description,
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function answers($id)
    {
        $answers = Answer::where('question_id',$id)->where('status',1)->with('checks','user.profile','question.user')->orderBy('updated_at','ASC')->paginate(1);

        if(@$answers){




            $clientIP = request()->ip();
            $status = 0;
            foreach($answers as $answer)
            {
                $questionId = $answer->question->id;
                $visitor = Visitor::where('ip_address',$clientIP)->where('question_id',$questionId)->where('answer_id',$answer->id)->first();
                if(!@$visitor)
                {
                    $visitor_ins = new Visitor;
                    $visitor_ins->ip_address = $clientIP;
                    $visitor_ins->question_id = $questionId;
                    $visitor_ins->answer_id = $answer->id;
                    $visitor_ins->save(); 

                    $status = 1; 
                }
            }


            return response()->json([
                'answers' => $answers,
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function correct_ans_check($slug)
    {

        $answer = Answer::where('slug',$slug)->first();
        if(@$answer){
            $clientIP = request()->ip();
            $check = Check::where('ip_address',$clientIP)->where('answer_id',$answer->id)->first();
            if(!@$check)
            {
                $check_ins = new Check;
                $check_ins->ip_address = $clientIP;
                $check_ins->answer_id = $answer->id;
                $check_ins->save();

                return response()->json([
                    'check' =>$check_ins,
                    'status' => 1,
                    'success' => 'Checked'
                ],201);

            }else{
                $check->delete();

                return response()->json([
                    'status' => 1,
                    'success' => 'Unchecked'
                ],201);
            }
        }
        else
        {
            return response()->json([
                'status' => 0,
                'alert' => 'Operation failed'
            ],400);
        }
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

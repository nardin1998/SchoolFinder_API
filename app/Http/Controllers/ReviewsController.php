<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use Validator;
class ReviewsController extends Controller
{
   public function __construct()
    {
        $this->middleware('auth')->except(['index','show']);////////////////////////////////////////////////////////////////////////////////////
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $reviews = Review::paginate(10);
        return response()->json($reviews,200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $restrictions=[
            
            "id"=> 'required',  //////////////////////////////////////////ask////////////////////////////////////////////////////
            "user_id"=> 'required',
            "school_id"=> 'required',
            'Review_description' => 'required|min:2|max:400',
            'rating'=> 'required',
            'created_at'=> 'required',	
            
        ];
        $validator= Validator::make($request->all(),$restrictions);
        if($validator->fails()){
            echo "This Review can't be stored it doesn't match our restrictions";
            echo "Content required min of characters:2 and max:400";
            return response()->json($validator->errors(),400);
        }
        
       $review=Review::create($request->all());
       return response()->json($review,201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $review = Review::find($id);
        if(is_null($review)){
            return response()->json(["message"=>"Response not Found!!"],404);
        }
        return response()->json($review,200);
        
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

        $review=Review::find($id);
        
        if(is_null($review)){
          return response()->json(["message"=>"This review is not found!"],404);
        }
        if (auth()->user()->id !== $review->user_id){////////////////////////////////////////////////////////////////////////////////////
            return response()->json(["message"=>"sorry you are not the review owner to update it :D"],401);
        }
        $restrictions=[
            'updated_at'=> 'required',
        ];
        $validator= Validator::make($request->all(),$restrictions);
        if($validator->fails()){
            echo "This Review can't be stored it doesn't match our restrictions";
            echo "specify the update date and time";
            return response()->json($validator->errors(),400);
        }
        $review->update($request->all());
        return response()->json($review,200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $review=Review::find($id);
        if(is_null($review)){
          return response()->json(["message"=>"This review is not found!"],404);
        }
        if (auth()->user()->id !== $review->user_id){////////////////////////////////////////////////////////////////////////////////////
            return response()->json(["message"=>"sorry you are not the review owner to delete it :D"],401);
        }
        $review->delete();
        return response()->json(null,204);
    }
}

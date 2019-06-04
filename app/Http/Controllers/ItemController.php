<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Item;
use App\Thematic;


class ItemController extends Controller
{

    // List All items
    public function listItems()
    {
        $items = Item::all();

        $media_url = array();

        // TODO create a service or middleware
        foreach($items as $item){
            
            // Todo Eloquent Relation One to One
            if($item->thematic_id){
                $thematic = Thematic::find($item->thematic_id);
            }
            
            $item->card_picture = asset('/medias').'/'.$item->card_picture;

                // gets the api absolute url to media
                foreach($item->medias as $media){
                    array_push($media_url,asset('/medias').'/'.$media );
                }
                $item->medias = $media_url;
                $item->thematic_name = $thematic->name;
                $media_url = array();
        }
    return $items;
    }

    // List a single item
    public function listItem($id)
    {
        $item = Item::find($id);
        $media_url = array();

        // gets the api absolute url to media
        // TODO create a service or middleware
        foreach($item->medias as $medias){
            echo asset('/').$medias;
            array_push($media_url,asset('/medias').'/'.$medias );
        }
        $item->card_picture = asset('/medias').'/'.$item->card_picture;
        $item->medias = $media_url;

        return $item;
    }

    // Create a new item
    public function createItem(Request $request)
    {
        
        // Gets every element from the form-data and stores to db
        $item = Item::create([
            'name' => $request->input('name'),
            'medias' => 'null',
            'card_id' => $request->input('card_id'),
            'card_picture' => 'null',
            //'thematic_id' => 'thematic_id',
        ]);

        // TODO Check file size and mime type (png/jpeg < 2mb)
        // TODO Compress if image 
        // TODO Check resolution on each use case
        
        $destinationPath = 'medias/'.$item->id;

        // Move the card picture to directory of item
        $cardPicture = $request->file('card_picture');
        $cardPicture->move($destinationPath,$cardPicture->getClientOriginalName());
        $card_picture_path = $item->id.'/'.$cardPicture->getClientOriginalName();

        $media_path = array();
        // For each defined zone move file to directory and add to $media_path Array
        for ($i=1; $i < 5; $i++) { 
            $file = $request->file('zone'.$i);
            if($file->getClientOriginalExtension() == 'png'){ 
                if($file){
                    array_push($media_path, $item->id.'/'.$file->getClientOriginalName());
                    $file->move($destinationPath,$file->getClientOriginalName());
                }
            }
            else {
                return "The zone medias need to be in png";
            }
        }
       
        // Fetch and update item in db
        //$updateItem = Item::find($item->id);
        $item->update([
            'medias' => $media_path,
            'card_picture' => $card_picture_path
        ]);
        $item->save();

        return response()->json($item, 201);
    }


    // Update a single item based on his id
    public function updateItem($id,Request $request)
    {
        $item = Item::findOrFail($id);


        $destinationPath = 'medias/'.$item->id;
        // Move the card picture to directory of item
        if($request->file('card_picture')){
            $cardPicture = $request->file('card_picture');
            $cardPicture->move($destinationPath,$cardPicture->getClientOriginalName());
            $card_picture_path = $item->id.'/'.$cardPicture->getClientOriginalName();
        }

        $media_path = array();
        
        // Checks if files are uploaded
        if($request->file){
            
            // For each defined zone move file to directory and add to $media_path Array
            for ($i=1; $i < 5; $i++) { 
                $file = $request->file('zone'.$i);
                if($file->getClientOriginalExtension() == 'png'){ 
                    array_push($media_path, $item->id.'/'.$file->getClientOriginalName());
                    $file->move($destinationPath,$file->getClientOriginalName());
                }
                else{
                    return "The zone medias need to be in png";
                }
            }
        }

        ($request->name) ? $item->update(['name' => $request->name,]) : '';
        ($request->card_id) ? $item->update(['card_id' => $request->card_id,]) : '';
        ($request->thematic_id) ? $item->update(['thematic_id' => $request->thematic_id,]) : '';
        ($request->medias) ? $item->update(['medias' => $request->$media_path,]) : '';
        ($request->card_picture) ? $item->update(['card_picture' => $card_picture_path,]) : '';
        return response()->json($item, 201);
    }

    
    // Delete a single item based on his id
    public function deleteItem($id)
    {
       $item = Item::findOrFail($id);
       $item->delete();
        return $item->name.' has been deleted';
    }
}

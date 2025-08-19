<?

public function cryptoRegistration(Request $request){
        
        $address = strtolower($request->input('usdt_address'));
        $signature = $request->input('signature');
        $message = $request->input('signed_message');

        $recovered = $this->recoverAddress($message, $signature);
    
        if (strtolower($recovered) !== $address) {
            return redirect()->back()->with('error', "Signature verification failed.");
        }
        
        $referal = $request->referal;
        
        $parent = DB::table('user')->where('userid', $referal)->first();
        if(!$parent){
            return redirect()->back()->with('error', 'Referral Not Found!');
        }
        
        if(DB::table('user')->where('tron_address', $address)->exists()){
           return redirect()->back()->with('error', 'This wallet is already registered!'); 
        }
        
        try{
            $prefix = DB::table('business_setup')->first()->id_prefix;
            $userid = $this->userId($prefix);
            
          $last_save_id = DB::table('user')->insertGetId([
                'userid' => $userid,
                'referal' => strtoupper($parent->userid),
                'tron_address' => $address,
            ]);
              $this->defaultSavePackage($last_save_id, $parent->id);
            return redirect()->route('login')->with('success', 'Registration successful! Your UserID : ' . $userid);
        
        }catch(\Exception $ex){
            return redirect()->back()->with('error', $ex->getMessage()); 
        }
        
    }
    public function userId($prefix = 'ABC')
    {
        do {
            $rand = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $userid = substr(str_replace(" ", "_", $prefix), 0, 3) . $rand;
        } while (DB::table('user')->where('userid', $userid)->exists());
    
        return $userid;
    }
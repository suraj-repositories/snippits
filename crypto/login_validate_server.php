<?php
use kornrunner\Ethereum\Address;
use kornrunner\Keccak;
use Elliptic\EC;
use kornrunner\Secp256k1;

function login_validates_crypto(Request $request){
    
        $address = strtolower($request->input('usdt_address'));
        $signature = $request->input('signature');
        $message = $request->input('signed_message');

        $recovered = $this->recoverAddress($message, $signature);
    
        if (strtolower($recovered) !== $address) {
            return redirect()->back()->with('error', "Signature verification failed.");
        }
        
        if(empty($address)){
            return redirect()->back()->with('error', 'The Wallet address not detected!');
        }
        
            $user=DB::table('user')->where('tron_address',$address)->first();
              if($user){
                  if($user->role != 'user'){
                      return redirect()->back()->with('error', 'This login is for users only!');
                  }
                  
                  
                Auth()->loginUsingId($user->id);
                  if(Auth::user()->role=='admin'){
                      return redirect()->route('dashboard');
                    }elseif(Auth::user()->role=='franchise'){
                     return redirect()->route('franchise-dashboard');
                    }else{
                        
                        $user_packagess=DB::table('user_package')->where('user_id',Auth::user()->id)->get();
                        if(empty($user_packagess['0']->id)){
                             return redirect()->route('user-dashboard')->with('login-success', 'Successfully login');
                        }else{
                             return redirect()->route('user-dashboard')->with('login-success', 'Successfully login');
                        }
                    }
              }
        
        
        return redirect()->back()->with('error', 'Login Failed! User with the given address not exists!');
    }


    function recoverAddress(string $message, string $signature): string
    {
        $msg = "\x19Ethereum Signed Message:\n" . strlen($message) . $message;
        $msgHash = Keccak::hash($msg, 256);

        $signature = substr($signature, 2); // remove 0x
        $r = substr($signature, 0, 64);
        $s = substr($signature, 64, 64);
        $v = hexdec(substr($signature, 128, 2));

        if ($v >= 27) {
            $v -= 27;
        }

        $ec = new EC('secp256k1');
        $pubKey = $ec->recoverPubKey($msgHash, ['r' => $r, 's' => $s], $v);
        $pubKeyHex = $pubKey->encode('hex');
        $pubKeyBody = substr($pubKeyHex, 2); // skip 0x04

        return '0x' . substr(Keccak::hash(hex2bin($pubKeyBody), 256), 24);
    }
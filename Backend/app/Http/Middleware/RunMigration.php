<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Closure;


class RunMigration
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param array $scopes
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        $req_method = $request->method();
        
            try{
                
                if( ($req_method === 'POST') || ($req_method === 'GET') ){
                    
                    //run remaining migration:
                    Artisan::call('migrate --force');
                }
                
            }catch( \Exception $ex){
                Artisan::call('migrate:rollback');
            }
            
        
        $response = $next($request);
        return $response;

    }
}

?>

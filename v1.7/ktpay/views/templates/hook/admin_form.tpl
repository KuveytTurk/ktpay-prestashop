{if !$err}
{{$err}}
{/if}
<link rel="stylesheet" type="text/css" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/w3.css">
<div class="panel">
    <div class="row ">
        <img src="{$module_dir|escape:'html':'UTF-8'}/img/kuveytturk.svg" class="col-xs-4 col-md-2 text-center" id="payment-logo" />
        <div class="col-xs-6 col-md-5 text-center">
        </div>
   
    </div>

    <hr />
    {$admin_settings}
    
</div>

<div class="panel">
    {$installment_settings}
</div>

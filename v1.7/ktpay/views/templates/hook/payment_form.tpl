<link rel="stylesheet" type="text/css" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/core.min.css">
<link rel="stylesheet" type="text/css" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/core.css">
<link rel="stylesheet" type="text/css" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/keyboard.css">

<div class="flex gap-4 flex-col px-5 max-w-full w-[600px]">
    <div class="flex flex-col flex-1">
        <div>   
            <img src="{$module_dir|escape:'htmlall':'UTF-8'}img/kuveytturk.svg" alt="" srcset="">
        </div>
        <div class="grow my-auto font-large leading-4 text-right text-teal-600">
            Ödeme Sayfası
        </div>
    </div

    <div class="flex flex-col max-md:px-5">
        <form method="POST" id="ktform" action="{$action}">
            <div id="alert" class="alert" style="display: none;">
                <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                <label id="alertText">Hata!</label>
            </div>
            <div class="mb-3">
                <label for="card-holder" class="block text-sm text-ellipsis text-zinc-600 mb-2">
                    Kart Sahibi Ad Soyad
                </label>
                <input type="text" id="card-holder" name="card-holder" style="color: black;"
                    onkeypress="restrictAllWithoutAlphabets(event)"
                    onpaste="return restrictPasteAllWithoutAlphabets(event)" onfocus="keyboardClose()"
                    class="flex w-full p-1 mt-1.5 text-neutral-400 bg-white rounded-md border border-solid border-[color:var(--Box-stroke,#D6D6D6)] shadow-lg"
                    placeholder="Ad Soyad" maxlength="45">
            </div>

            <div class="mb-3">
                <label for="card-number" class="block text-sm text-ellipsis text-zinc-600 mb-2">
                    Kart Numarası
                </label>
                <div class="flex gap-3 justify-center self-center justify-between mb-3" style="position:relative">
                    <input type="text" id="card-number" name="card-number" style="color: black;"
                        onpaste="return restrictPasteAllWithoutNumsWithSpace(event)"
                        onkeypress="restrictAllWithoutNums(event)" onkeyup="cardNumberControls(event,null)"
                        class="flex w-full p-1 mt-1.5 text-neutral-400 bg-white rounded-md border border-solid border-[color:var(--Box-stroke,#D6D6D6)] shadow-lg"
                        placeholder="1234 1234 1234 1234" minlength="19">
                    <img id="keyboard"
                        src="{$module_dir|escape:'htmlall':'UTF-8'}img/keyboard.png"
                        loading="lazy" class="max-w-16 mt-4" style="position: absolute; bottom: 10px;  right: 10px"
                        onclick="keyboardClick()" alt="" />
                    <img id="visaScheme"
                        src="{$module_dir|escape:'htmlall':'UTF-8'}img/visa.svg"
                        loading="lazy"
                        style="display: none; width:60px; margin-top:24px; margin-right: 40px; position: absolute; right: 10px"
                        class="max-w-16 mt-4" alt="" />
                    <img id="troyScheme"
                        src="{$module_dir|escape:'htmlall':'UTF-8'}img/troy.png"
                        loading="lazy"
                        style="display: none; width: 60px; margin-right: 40px; position: absolute; top: 2px; right: 10px"
                        class="max-w-16 mt-4" alt="" />
                    <img id="masterCardScheme"
                        src="{$module_dir|escape:'htmlall':'UTF-8'}img/mastercard.svg"
                        loading="lazy"
                        style="display: none; width: 50px; margin-right: 40px; position: absolute; top: 2px; right: 10px"
                        class="max-w-12 mt-4" alt="" />
                </div>
                <div id="virtualKeyboard" class="virtual-keyboard" style="display: none;">
                    <div><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">1</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">2</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">3</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">4</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">5</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">6</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">7</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">8</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">9</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)">0</span><span class="key" onmousedown="typeVirtualKeyboardKey(this.innerHTML)" style="width: 100px;"><</span></div>
                </div>
            </div>

            <div class="flex gap-4 justify-between mb-3">
                <div class="flex flex-col flex-1" style="align-items: flex-start;">
                    <label for="card-expire" class="block text-sm text-ellipsis text-zinc-600">
                        Kart Son Kullanma Tarihi
                    </label>
                    <div class="flex gap-4 justify-between">
                        <input type="text" id="card-expire-date" name="card-expire-date" style="color: black;" onkeyup="checkCardExpireDate(event)" onkeypress="restrictAllWithoutNums(event)" onpaste="event.preventDefault();"
                        class="flex w-full p-1 mt-1.5 text-neutral-400 bg-white rounded-md border border-solid border-[color:var(--Box-stroke,#D6D6D6)] shadow-lg"
                        placeholder="AA/YY" maxlength="5" onfocus="keyboardClose()"> 
                    </div>
                </div>
                <div class="flex flex-col flex-1" style="align-items: flex-start;">
                    <label for="card-cvv" class="block text-sm text-ellipsis text-zinc-600">CVC/CVV</label>
                    <input type="text" id="card-cvv" name="card-cvv" style="color: black;"
                        onkeypress="restrictAllWithoutNums(event)"
                        onpaste="return restrictPasteAllWithoutNums(event)" onfocus="keyboardClose()"
                        class="flex w-full p-1 mt-1.5 text-neutral-400 bg-white rounded-md border border-solid border-[color:var(--Box-stroke,#D6D6D6)] shadow-lg"
                        placeholder="123" maxlength="3">
                </div>
            </div>
            <div id="installmentDiv" class="flex gap-4 justify-between" style="display: none;">
                <div class="flex flex-col flex-1" style="align-items: flex-start;">
                    <label for="installment-number"
                        class="block text-sm text-ellipsis text-zinc-600">Taksit Adedi</label>
                    <div class="flex gap-4 p-1 mt-1.5 justify-center w-full flex-row flex-1 rounded-md border border-solid border-[color:var(--Box-stroke,#D6D6D6)]"
                        style="flex-wrap:wrap">
                        {if $installment_count!=null and $installment_count>1 }
                            <table>
                                {for $i=1 to $installment_count+1}
                                    {if $rates[$i]['active']==1}
                                        <tr>
                                            <th>
                                                <div style="width:100px; display: flex; flex-direction:row;">
                                                    <input type="radio" id="{$rates[$i]['count']}" name="installment"
                                                        value="{$rates[$i]['count']}" >
                                                    <label
                                                        id="installmentLabel_{$rates[$i]['count']}" style="margin-bottom:1px; margin-left:5px;">
                                                        {if $rates[$i]['count']==1 }
                                                            Tek Çekim
                                                        {else}
                                                            {$rates[$i]['count']} Taksit
                                                        {/if}
                                                    </label>
                                                </div>
                                            </th>
                                            <th>
                                                <div>
                                                    <label>{$rates[$i]['total']} / {$rates[$i]['monthly']}</label>
                                                </div>
                                            </th>
                                        </tr>
                                    {/if}
                                {/for}
                            </table>
                        {/if}
                    </div>
                </div>
            </div>
            <div class="flex flex-col py-4 pl-12 mt-4 max-md:pl-5">
                <div class="flex gap-4 justify-between px-2">
                    <button id="payButton" type="button" onclick="pay()"
                        class="grow px-16 py-1 font-medium text-center text-white bg-teal-600 rounded-xl shadow-lg max-w-[400px]">
                        <i id="spinner" class="fa fa-spinner fa-spin" style="display:none;"></i>
                        <i id="buttonText">Öde</i>
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

<script src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/core.js"></script>
<script src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/keyboard-handler.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>

<script type="text/javascript">
    var installmentMode = "{$installment_mode}";
    var hasRightForInstallment = "{$has_installment}";
    var checkOnusCardUrl = "{$check_onus_card_url}";
    var isCheckOnUsCard = false;
</script>
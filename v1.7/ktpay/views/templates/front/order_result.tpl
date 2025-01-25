
{extends file='page.tpl'}
{block name="content"}
<p>
	{if !empty({$error})}
		<div class="alert alert-danger">
			HATA!  Ödeme işleminiz tamamlanamadı. <br>
			<p>Banka Cevabı :   {$error}</p>
		</div>      
	{else}
		<div class="alert alert-success">
			<p>{$order_id} Sipariş Id'li Ödeme İşleminiz Başarıyla Tamamlandı. Teşekkürler</p>		
		</div>
    {/if}
</p>
{/block}
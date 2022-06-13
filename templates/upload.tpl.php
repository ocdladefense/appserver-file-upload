

<h2>OCDLA: Upload a File</h2>

<div class="container">
	<form method="post" action="/file/upload" enctype="multipart/form-data">

		<div class="form-item">
			<div>
				<input type="file" id="Attachments__c[]" name="Attachments__c[]" />
			</div>
		</div>

		<div class="form-item">
			<input type="submit" value="Upload File" />
		</div>

	</form>
</div>



<style>
.container {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    border: 3px solid rgba(0,0,0,.125);
    border-radius: .25rem;
	padding: 10px;
	margin-top:10px;
}
</style>
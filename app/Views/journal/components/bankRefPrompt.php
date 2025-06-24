<script>
  function getUserInput(message){
    return prompt(message); 
  }
</script>


<div class="modal fade" id="myPromptModal" tabindex="-1" aria-labelledby="myPromptModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myPromptModalLabel">Please Enter Your Input</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="promptMessage">Enter your text here:</p>
        <input type="text" class="form-control" id="promptInput">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="promptOkButton">OK</button>
      </div>
    </div>
  </div>
</div>
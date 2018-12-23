<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
 <script src="/media/js/functions.js"></script>


 
<div class="frame">
   
   <div class="letters">
   </div>
</div>
   <div class="clearfix"></div>
<div class="form-group row">
   <label for="selectedWord" class="col-sm-3 col-form-label">
      Digite a sua palavra:
   </label>
   <div class="col-sm-5">
      <input type="text" class="form-control" id="selectedWord" placeholder="DECOPHOTO">
   </div>
</div>
<div class="form-group row">
   <label for="paspatur" class="col-sm-3 col-form-label">
      Selecione o paspatur:
   </label>
   <div class="col-sm-5">
      <select class="form-control" name="paspatur" id="paspatur" onchange="selectPaspatur();">
         <option value="">Selecione</option>
         <option value="1">Preto</option>
         <option value="2">Branco</option>
         <option value="3">Chocolate</option>
         <option value="4">Creme</option>
      </select>
   </div>
</div>
<div class="form-group row">
   <label for="frame" class="col-sm-3 col-form-label">
      Selecione a moldura:
   </label>
   <div class="col-sm-5">
      <select class="form-control" name="frame" id="frame" onchange="selectFrame();">
         <option value="" selected>Selecione</option>
         <option value="1" selected>Moldura 1</option>
         <option value="2">Moldura 2</option>
         <option value="3">Moldura 3</option>
         <option value="4">Moldura 4</option>
         <option value="5">Moldura 5</option>
         <option value="6">Moldura 6</option>
         <option value="7">Moldura 7</option>
      </select>
   </div>
</div>
<div class="">
   <button class=" mx-auto btn btn-success" type="button" onclick="setWord()">Visualizar</button>
   <button class=" mx-auto btn btn-success" type="button" onclick="addItemCart()">Adicionar no carrinho</button>
</div>
<div class="modal fade" id="letter-modal" tabindex="-1" role="dialog" aria-labelledby="letter-modal-title" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="letter-modal-title">Selecione imagem da letra</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div id="choose-letter-modal" class="modal-body">
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(setWord())
   
</script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>


 <link rel="stylesheet" type="text/css" href="/media/css/style.css"></script>

(document).ready(function() {
        $('#getCredentialsButton').click(function(){
                var urlJSON = "applib/objStorage.php";
                var credential_data = "";
                $.ajax({
                        async: true,
                        type: 'GET',
                        url: urlJSON,
                        data: {'action': 'getOpenstackUser'}
                }).done(function(data) {
                        credential_data = data;
                });
    console.log(credential_data) }
}

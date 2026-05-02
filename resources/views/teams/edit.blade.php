@extends('layouts.main')

@section('title')
Edit User
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="card">
    <div class="card-body">
        <h1 class="mb-4">Edit User</h1>

        <form action="{{ route('teams.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ $user->name }}" readonly required>
                </div>

                <div class="mb-3 col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" value="{{ $user->email }}" readonly required>
                </div>

                <!-- <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" name="role" id="role" required>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                </div> -->

                <div class="mb-3">
                    <label for="networkid" class="form-label">Network</label>
                    <select id="networkSelect" onchange="getusers()" class="form-control" name="networkid" required>
                        <option value="">Select Network</option>
                        @foreach($networks as $network)
                        <option value="{{ $network->id }}" {{ $user->networkid == $network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="siteid" class="form-label">Site</label>
                    <select id="sitelist" class="form-control" name="siteid" required>
                        <option value="">Select Site</option>
                        @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ $user->siteid == $site->id ? 'selected' : '' }}>{{ $site->site_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- <div class="mb-3 col-md-6">
                    <label for="password" class="form-label">New Password (Optional)</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current">
                </div>

                <div class="mb-3 col-md-6">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="password_confirmation" placeholder="Leave blank to keep current">
                </div> -->

                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>


<script>
    function getusers() {
        var networkId = document.getElementById('networkSelect').value;
       // alert('Selected Network ID: ' + networkId);

        // var form = $("#consultantform").serialize();
       // var formid = '#_' + id;
        $("#sitelist").empty();
       // let form = new FormData($(formid).get(0));
       // var name = $('#search').val();
       // var four = $("#search").val().length
       // alert(four);

        
       if (networkId) {
            $.ajax({
                method: 'Get',
                url: '/teams/getsites/'+ networkId,
                contentType: "application/json; charset=utf-8",
                //data: { networkid: networkId },
               // datatype: 'text',
                
                success: function (datab) {
                    console.log(datab);
                  //  if (datab.success == "ok") {

                        var html_to_appendregistered = '';
                        $.each(datab, function (i, item) {

                            html_to_appendregistered += '<option value="' + item.id + '">' + item.site_name + '</option>';

                            //
                            // $("#resultstat").append(document.html_to_append("<br>"));


                       });
                       $('#sitelist').append(html_to_appendregistered);

                       // toastr.success("Consultant already Available!!", { tapToDismiss: true });

                        //$('#representativeinfo').html('<tr><td>' + datab.data.name + ' ' + datab.data.surname + '</td><td>' + datab.data.natid + '</td><td>' + datab.data.email + '</td><td>' + datab.data.physicalAddress + '</td><tr>');
                   // } else if (datab.success = "err" && datab.data == "") {

                       // toastr.error("Consultant already Available!!", { tapToDismiss: true });
                   // }



                },
                error: function (datab) {
                    //  $('#returninfo').html('could not find user with the provided email');
                   // toastr.error("Error Encountered!!", { tapToDismiss: true });
                }
            });
        }

        


     }
</script>




@endsection

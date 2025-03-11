<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form onsubmit="handlesubmit(event)" enctype="multipart/form-data">
        <input type="file" name="image" id="image-input" />
        <input type="text" name="name" id="name-input" />
        <button type="submit">Validate</button>
    </form>
    <script>
        let formData = new FormData();
        // const formData = {
        //     append: (key, value) => {
        //         formData[key] = value;
        //     }
        // }

        async function handlesubmit(e) {
            e.preventDefault();

            for (let i = 0; i < e.target.length - 1; i++) {
                const { name, value, type, files } = e.target[i];
                formData.append(type === 'file' ? `${name}[]` : name, type === 'file' ? files[0] : value);
            }

            // const { append, ...payload } = formData;
            // console.log(payload);

            try {
                const response = await fetch('http://localhost:8000/api/user/update', {
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        Authorization: 'Bearer 7|8R3ygZNQFwSdlf9q2Z6quA94NAJNNhugr9iKAuO96396c90b',
                        // 'Content-Type': 'application/json'
                    },
                    method: 'PUT'
                });

                console.log(response);
            } catch (e) {
                console.log(e);
            }
        }
    </script>
</body>

</html>
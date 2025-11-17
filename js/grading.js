function postLTI(ses, name) {
    console.log("--- GRADING.JS: ATTEMPTING TO FETCH postLTI.php NOW ---");

    // The postLTI.php script expects a POST parameter named 'data'
    // whose value is a JSON string of the LTI session data.
    const dataAsJsonString = JSON.stringify(ses);
    const body = `data=${encodeURIComponent(dataAsJsonString)}`;

    const url = `/LTI/postLTI.php?name=${encodeURIComponent(name || '')}`;

    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .catch(error => {
        console.error('Error in postLTI:', error);
        throw error;
    });
}
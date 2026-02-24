fetch("api/reviews.php")
  .then(res => res.json())
  .then(async data => {
    const container = document.getElementById("restaurantList");

    if (!Array.isArray(data) || data.length === 0) {
      container.innerHTML = "<p>No restaurants found.</p>";
      return;
    }

    container.innerHTML = "";

    for (const item of data) {
      const div = document.createElement("div");
      div.className = "restaurant";

      let weatherHTML = "";

      if (item.location?.lat && item.location?.lng) {
        try {
          const weatherRes = await fetch(`api/weather.php?lat=${item.location.lat}&lng=${item.location.lng}`);
          const weather = await weatherRes.json();

          weatherHTML = `
            <p><strong>Weather:</strong> ${weather.condition} | ${weather.temperature}°C | Wind: ${weather.wind_speed} km/h</p>
          `;
        } catch (e) {
          weatherHTML = `<p><em>Weather unavailable</em></p>`;
        }
      }

      div.innerHTML = `
        <h3>${item.name}</h3>
        <p><strong>Address:</strong> ${item.address}</p>
        <p><strong>Phone:</strong> ${item.phone ?? 'N/A'}</p>
        <p><strong>Rating:</strong> ${item.rating ?? 'N/A'} (${item.reviews} reviews)</p>
        <p><strong>Map:</strong> <a href="${item.url}" target="_blank">View on Google Maps</a></p>
        ${weatherHTML}
        <hr>
      `;

      container.appendChild(div);
    }
  })
  .catch(err => {
    document.getElementById("restaurantList").innerHTML = `<p>Error loading restaurants.</p>`;
    console.error(err);
  });
<form onSubmit={handleBookingAssignJobSubmit} className="common_form_error" id="">
  <table className="table table-striped">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Chef's Name</th>
        <th scope="col">Menu</th>
        <th scope="col">Amount</th>
        <th scope="col">Client Amount</th>
        <th scope="col">Admin Amount</th>
      </tr>
    </thead>
    <tbody>
      {chefoffer.length > 0 ? (
        chefoffer.map((chef, index) => (
          <tr key={index}>
            <th scope="row">
              <div className="form-check">
                <input
                  className="form-check-input"
                  type="radio"
                  name="flexRadioDefault"
                  value={chef.id}
                  onChange={(e) => setChefId(e.target.value)}
                  ref={radioRef}
                />
              </div>
            </th>
            <td>{chef.name}</td>
            <td>
              {chef.menu_names?.split(",").map((menu, index) => (
                <button className="table-btn btn-2 list-btn" key={index}>
                  {menu.trim()}
                </button>
              ))}
            </td>
            <td>{chef.amount}</td>
            <td>
              <div className="all-form p-0">
                <div className="login_div">
                  <input
                    type="text"
                    id="amount"
                    name="amount"
                    placeholder="Client Amount"
                  />
                </div>
              </div>
            </td>
            <td>
              <div className="all-form p-0">
                <div className="login_div">
                  <input
                    type="text"
                    id="amount"
                    name="amount"
                    placeholder="Admin Amount"
                  />
                </div>
              </div>
            </td>
          </tr>
        ))
      ) : (
        <tr>
          <td colSpan="6">No Chef apply for this booking</td>
        </tr>
      )}
    </tbody>
  </table>
  <div className="text-right">
    <div className="banner-btn">
      <button id="btn_offer" className="mx-2" type="button" onClick={handleClear}>
        Clear
      </button>
      <button id="btn_offer" type="submit">
        Assign Booking
      </button>
    </div>
  </div>
</form>

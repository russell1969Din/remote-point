import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';

class App extends React.Component {

  constructor(props) {
    const protection = 'ABNet';
    const SQL = 'SELECT pers_id, pers_name, pers_surname, pers_birth, street_zip, street_name, ' +
      'pers_number, pers_color, city_name FROM person, street, city ' +
      'WHERE pers_idStreet = street_id AND street_city = city_id ';
    const URL = 'https://vt.abnet.sk';
    const user = 'userRemotePoint';
    const pass = 'enigma';
    const serial = '123-ABC';
    const prefix = 'apiTest';
    const childrenData = 'pers_id=>relJobs_persId(relJobs_jobId=>jobs_id):|:' +
      'pers_id=>open_persId(open_day=>days_id):|:' +
      'pers_id=>relKnow_persId(relKnow_knowId=>know_id:||:relKnow_degreeId=>degree_id )';

    super(props);
    this.state = {
      protection: protection,
      URL: URL,
      SQL: SQL,
      user: user,
      pass: pass,
      serial: serial,
      prefix: prefix,
      childrenData: childrenData,
      content: [],
      isLoaded: false,
      error: null,

    }
  }



  componentDidMount() {

    const { protection, SQL, URL, user, pass, serial, prefix, childrenData } = this.state;

    let params = 'protection=' + protection;
    params += '&SQL=' + SQL;
    params += '&user=' + user;
    params += '&pass=' + pass;
    params += '&serial=' + serial;
    params += '&prefix=' + prefix;
    params += '&childrenData=' + childrenData;
    params += '&localhost=localhost'
    const requestOptions = {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded'
        //'Content-Type': 'application/json'
      },
      body: params
    }
    fetch(URL, requestOptions)
      .then(res => res.json())
      .then(
        (result) => {
          console.log(typeof result)

          if (Object.keys(result).length > 0) {
            let line = result[0];

            //console.log(typeof line.open);

            if (typeof line.error != 'undefined') {
              result.map((error) => console.log(error.error));
              result = [];
            }
          }

          this.setState({
            isLoaded: true,
            content: result
          })
        }
      )
      .then(
        (error) => {
          this.setState({
            isLoaded: false,
            error
          })
        }
      )
  }

  render() {
    const { content, isLoaded, error } = this.state;

    if (error) {
      return <div>Error: {error.message}</div>
    } else if (isLoaded) {
      return <div>LOADING ...</div>
    } else {
      return (
        <div className="global">{
          content.map((person, index) => (
            <div key={person.pers_id} className="element" style={{ backgroundColor: person.pers_color }}>
              <div className="row">
                <div className="col-sm-3 inLine mt-1">
                  Meno a priezvisko:
              </div>
                <div className="col-sm-9 inAfter mt-1">
                  <b>{person.pers_name} {person.pers_surname} &nbsp;&nbsp;&nbsp;&nbsp; (Personálne číslo: {person.pers_id})</b>
                </div>
              </div>
              <div className="row">
                <div className="col-sm-3 inLine mt-1">
                  Trvalé bydlisko:
                </div>
                <div className="col-sm-9 inAfter mt-1">
                  <b>{person.street_name} {person.pers_number}</b>
                </div>
              </div>
              <div className="row">
                <div className="col-sm-3 inLine mt-1">
                  Sídlo:
                </div>
                <div className="col-sm-9 inAfter mt-1">
                  <b>{person.street_zip} {person.city_name}</b>
                </div>
              </div>
              <div className="row">
                <div className="col-sm-3 inLine mt-1">
                  Profesia:
                </div>
                <div className="col-sm-9  mt-1">
                  <ul className="small">
                    {person.relJobs.map(jobLine =>
                      <li key={jobLine.jobs_id}>{jobLine.jobs_name}</li>
                    )}
                  </ul>
                </div>
              </div>
              <div className="row">
                <div className="col-sm-3 inLine mt-1">
                  Znalosti:
                </div>
                <div className="col-sm-9  mt-1">
                  <ul className="small">
                    {person.relKnow.sort((a, b) => (a.know_name > b.know_name) ? 1 : -1)
                      .map((lineKnow) =>
                        <li key={lineKnow.know_id + '_' + lineKnow.pers_id}>{lineKnow.know_name} &nbsp;&nbsp;&nbsp;({lineKnow.degree_name})</li>
                      )}
                  </ul>
                </div>
              </div>
              <div className="row">
                <div className="col-sm-3 inline mt-1">
                  K dispozícii je:
                </div>
                <div className="col-sm-9  mt-1">
                  <table style={{ "width": "100%" }}>
                    <thead >
                      <tr>
                        <td style={{ "width": "25%" }}>
                          <b>Dňa:</b>
                        </td>
                        <td style={{ "width": "25%" }}>
                          <b>OD:</b>
                        </td>
                        <td style={{ "width": "25%" }}>
                          <b>DO:</b>
                        </td>
                        <td style={{ "width": "25%" }}></td>
                      </tr>
                    </thead>
                    {
                      person.open.sort((a, b) => (a.days_id > b.days_id) ? 1 : -1)
                        .map(openLine =>
                          <tbody key={openLine.days_id + '_' + openLine.pers_id}>
                            <tr >
                              <td>{openLine.days_name}:</td>
                              <td>{openLine.open_from}</td>
                              <td>{openLine.open_to}</td>
                            </tr>
                          </tbody>
                        )}
                  </table>
                </div>
              </div>
            </div>
          ))
        }</div >
      )
    }
  }
}
ReactDOM.render(<App />, document.querySelector('#root'));


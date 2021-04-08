import React, { Component } from "react";

export class RemotePoint extends Component {
    //_isMounted = false;
    constructor(props) {
        super(props)
        this.url = 'https://vt.abnet.sk';
        this.parameters = '';
        this.parameters += 'protection=ABNet';
        this.state = {
            content: [],
            isLoading: false
        }
    }
    //componentDidMount() { this._isMounted = true; }

    componentDidMount() {
        //console.log(this.parameters);
        const requestOptions = {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
                //'Content-Type': 'application/json'
            },
            body: this.parameters
        }
        //console.log(this.url)
        fetch(this.url, requestOptions)
            .then(res => res.json())
            .then((result) => {
                console.log('out');
                console.log(result);
                this.setState({
                    content: result,
                    isLoading: true,
                })
            })
            .then((error) => {
                this.setState({
                    isLoading: false,
                    error
                })
            })

    }

    render() {
        return (<div></div>);
    }
}

export default RemotePoint;
